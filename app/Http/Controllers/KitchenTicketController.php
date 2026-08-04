<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ShopOrder;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

class KitchenTicketController extends Controller
{
    public function sendKitchenTicket(Request $request)
    {
        $shopOrderId = $request->input('shop_order_id');
        $items = json_decode($request->input('items'), true);

        if (!$shopOrderId || empty($items)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data.'
            ]);
        }

        DB::beginTransaction();

        try {
            $order = DB::table('shop_order as so')
                ->join('shop_register as sr', 'sr.id', '=', 'so.shop_register_id')
                ->where('so.id', $shopOrderId)
                ->select(['so.*', 'sr.is_restaurant'])
                ->first();

            if (!$order) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.'
                ]);
            }

            if ($order->is_restaurant === 'Yes' && (empty($order->table_number) || is_null($order->floor_plan_table_id))) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'A table selection is required for restaurant register orders.'
                ]);
            }

            $resolvedItems = [];

            foreach ($items as $item) {
                $orderItemId = $item['shop_order_item_id'];
                $actionType = $item['action_type'];

                $orderItem = DB::table('shop_order_item')->where('id', $orderItemId)->first();

                if (!$orderItem) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Order item with ID {$orderItemId} not found."
                    ]);
                }

                $orderNote = $item['order_note'] 
                    ?? $item['note'] 
                    ?? $item['remarks'] 
                    ?? $orderItem->order_note 
                    ?? null;

                $item['order_note'] = $orderNote;
                $item['order_item_record'] = $orderItem;

                $originalRoute = DB::table('kitchen_ticket_item as kti')
                    ->join('kitchen_ticket as kt', 'kt.id', '=', 'kti.kitchen_ticket_id')
                    ->where('kti.shop_order_item_id', $orderItemId)
                    ->whereIn('kti.action_type', ['New', 'Add', 'Refire', 'Reduce', 'Cancel'])
                    ->orderByDesc('kti.id')
                    ->select(['kt.kitchen_route_id', 'kt.kitchen_route_name'])
                    ->first();

                if ($actionType === 'New') {
                    $route = DB::table('kitchen_route')->where('id', $item['kitchen_route_id'] ?? null)->first();

                    if (!$route) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid or missing kitchen route.'
                        ]);
                    }

                    $finalRouteId = $route->id;
                    $finalRouteName = $route->kitchen_route_name;
                } else {
                    if (!$originalRoute) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Unable to determine original kitchen route.'
                        ]);
                    }

                    $finalRouteId = $originalRoute->kitchen_route_id;
                    $finalRouteName = $originalRoute->kitchen_route_name;
                }

                $item['kitchen_route_id'] = $finalRouteId;
                $item['kitchen_route_name'] = $finalRouteName;
                $resolvedItems[] = $item;
            }

            $grouped = collect($resolvedItems)->groupBy('kitchen_route_id');
            $createdTickets = [];
            $ticketsToPrint = [];

            foreach ($grouped as $routeId => $routeItems) {
                $routeName = $routeItems->first()['kitchen_route_name'];

                // FIX A: Always find the latest active OR completed ticket for this order & route
                $existingTicket = DB::table('kitchen_ticket')
                    ->where('shop_order_id', $shopOrderId)
                    ->where('kitchen_route_id', $routeId)
                    ->orderByDesc('id')
                    ->first();

                $isBrandNewTicket = false;

                if ($existingTicket) {
                    $ticketId = $existingTicket->id;
                } else {
                    $isBrandNewTicket = true;

                    $inserted = false;
                    $attempts = 0;

                    while (!$inserted && $attempts < 5) {
                        $attempts++;
                        
                        $ticketNumber = sprintf(
                            'KT-%s-%s-R%d-%s',
                            now()->format('Hi'),
                            substr($order->order_number, -3),
                            $routeId,
                            strtoupper(substr(bin2hex(random_bytes(2)), 0, 4))
                        );

                        try {
                            $ticketId = DB::table('kitchen_ticket')->insertGetId([
                                'ticket_number'      => $ticketNumber,
                                'shop_order_id'      => $shopOrderId,
                                'shop_register_id'   => $order->shop_register_id,
                                'shop_register_name' => $order->shop_register_name,
                                'kitchen_route_id'   => $routeId,
                                'kitchen_route_name' => $routeName,
                                'ticket_status'      => 'Queued',
                                'queued_at'          => now(),
                                'created_at'         => now(),
                                'created_by'         => auth()->id(),
                                'created_by_name'    => auth()->user()->name ?? 'System',
                            ]);
                            $inserted = true;
                        } catch (\Illuminate\Database\QueryException $qe) {
                            if ($qe->getCode() == '23000' && $attempts < 5) {
                                continue;
                            }
                            throw $qe;
                        }
                    }
                }

                $insertedItemIds = [];
                $cancelledItemSlips = [];

                foreach ($routeItems as $item) {
                    $orderItem = $item['order_item_record'];

                    if (in_array($item['action_type'], ['Reduce', 'Cancel'])) {
                        $qtyToReduce = $item['quantity'];

                        // FIX C: Look across ALL non-cancelled items associated with this shop order item
                        $activeKitchenLines = DB::table('kitchen_ticket_item')
                            ->where('shop_order_item_id', $item['shop_order_item_id'])
                            ->where('item_status', '!=', 'Cancelled')
                            ->orderBy('id', 'desc')
                            ->get();

                        foreach ($activeKitchenLines as $line) {
                            if ($qtyToReduce <= 0) break;

                            $reducedQty = min($line->quantity, $qtyToReduce);

                            if ($line->quantity <= $qtyToReduce) {
                                $qtyToReduce -= $line->quantity;

                                DB::table('kitchen_ticket_item')
                                    ->where('id', $line->id)
                                    ->update([
                                        'item_status'  => 'Cancelled',
                                        'cancelled_at' => now(),
                                        'updated_at'   => now()
                                    ]);
                            } else {
                                DB::table('kitchen_ticket_item')
                                    ->where('id', $line->id)
                                    ->decrement('quantity', $qtyToReduce);
                                
                                $qtyToReduce = 0;
                            }

                            $cancelledItemSlips[] = [
                                'product_name' => $line->product_name,
                                'quantity'     => $reducedQty,
                                'action_type'  => $item['action_type'],
                                'order_note'   => $item['order_note'] ?? $line->order_note ?? null
                            ];
                        }
                    } else {
                        $newItemId = DB::table('kitchen_ticket_item')->insertGetId([
                            'kitchen_ticket_id'  => $ticketId,
                            'shop_order_item_id' => $item['shop_order_item_id'],
                            'product_id'         => $orderItem->product_id ?? null,
                            'product_name'       => $orderItem->product_name ?? null,
                            'action_type'        => $item['action_type'],
                            'quantity'           => $item['quantity'],
                            'order_note'         => $item['order_note'],
                            'item_status'        => 'Queued',
                            'queued_at'          => now(),
                            'created_at'         => now(),
                            'created_by'         => auth()->id(),
                            'created_by_name'    => auth()->user()->name ?? 'System',
                        ]);

                        $insertedItemIds[] = $newItemId;
                    }

                    DB::table('shop_order_item')
                        ->where('id', $item['shop_order_item_id'])
                        ->update([
                            'item_status' => $this->mapKitchenStatus($item['action_type']),
                            'order_note'  => $item['order_note'],
                            'last_log_by' => auth()->id(),
                            'updated_at'  => now(),
                        ]);
                }

                $this->recalculateTicketStatus($ticketId);
                $createdTickets[] = $ticketId;
                
                if ($isBrandNewTicket || !empty($insertedItemIds) || !empty($cancelledItemSlips)) {
                    $ticketsToPrint[] = [
                        'ticket_id'            => $ticketId,
                        'route_id'             => $routeId,
                        'is_brand_new'         => $isBrandNewTicket,
                        'inserted_item_ids'    => $insertedItemIds,
                        'cancelled_item_slips' => $cancelledItemSlips
                    ];
                }
            }

            DB::commit();

            // FIX B: Separate dispatch logic for additions vs cancellations
            foreach ($ticketsToPrint as $printJob) {
                // Print additions/new items
                if ($printJob['is_brand_new'] || !empty($printJob['inserted_item_ids'])) {
                    $this->dispatchDirectPrint(
                        ticketId: $printJob['ticket_id'],
                        routeId: $printJob['route_id'],
                        isAddition: !$printJob['is_brand_new'],
                        insertedItemIds: $printJob['inserted_item_ids'],
                        isCancellation: false,
                        cancelledItemSlips: []
                    );
                }

                // Print cancellation slip separately
                if (!empty($printJob['cancelled_item_slips'])) {
                    $this->dispatchDirectPrint(
                        ticketId: $printJob['ticket_id'],
                        routeId: $printJob['route_id'],
                        isAddition: false,
                        insertedItemIds: [],
                        isCancellation: true,
                        cancelledItemSlips: $printJob['cancelled_item_slips']
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Sent to kitchen successfully and printed.',
                'tickets' => $createdTickets
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send raw ESC/POS commands directly to the printer IP assigned to the kitchen route.
     */
    private function dispatchDirectPrint(
        int $ticketId, 
        int $routeId, 
        bool $isAddition = false, 
        array $insertedItemIds = [],
        bool $isCancellation = false,
        array $cancelledItemSlips = []
    ): void {
        try {
            $route = DB::table('kitchen_route')->where('id', $routeId)->first();
            if (!$route || empty($route->printer_ip)) {
                return; // No printer IP configured
            }

            $kitchenTicket = DB::table('kitchen_ticket')->where('id', $ticketId)->first();
            $shopOrder = ShopOrder::with(['items', 'floorPlan', 'floorPlanTable'])->find($kitchenTicket->shop_order_id);
            
            $ticketItems = [];

            if ($isCancellation && !empty($cancelledItemSlips)) {
                $ticketItems = $cancelledItemSlips;
            } else {
                $ticketItemsQuery = DB::table('kitchen_ticket_item')->where('kitchen_ticket_id', $ticketId);
                
                if ($isAddition && !empty($insertedItemIds)) {
                    $ticketItemsQuery->whereIn('id', $insertedItemIds);
                }
                
                $ticketItems = $ticketItemsQuery->get();
            }

            if (empty($ticketItems)) {
                return;
            }

            $connector = new NetworkPrintConnector($route->printer_ip, $route->printer_port ?? 9100);
            $printer = new Printer($connector);

            $printer->initialize();
            
            // --- HEADER ---
            $printer->setEmphasis(true);
            $printer->setTextSize(2, 2);
            
            $floorPlanName = optional($shopOrder->floorPlan)->name ?? '';
            $locationOrId = $shopOrder->table_number 
                ? 'TBL ' . $shopOrder->table_number 
                : '#' . $shopOrder->order_number;
            $headerText = trim($floorPlanName . ' ' . $locationOrId);
            
            $printer->text($this->centerTextEscPos($headerText, 16) . "\n");
            
            $printer->setTextSize(1, 1);
            $printer->initialize();

            // Print Header Badges
            if ($isCancellation) {
                $printer->setEmphasis(true);
                $printer->text($this->centerTextEscPos("*** ITEM VOID / CANCEL ***", 32) . "\n");
                $printer->setEmphasis(false);
            } elseif ($isAddition) {
                $printer->setEmphasis(true);
                $printer->text($this->centerTextEscPos("*** ADD-ON ORDER ***", 32) . "\n");
                $printer->setEmphasis(false);
            }
            
            $printer->text("TICKET: " . ($kitchenTicket->ticket_number ?? $shopOrder->order_number) . "\n");
            $printer->text("TIME: " . now()->format('h:i A | M d') . "\n");
            $printer->text(str_repeat("-", 32) . "\n");
            $printer->text(sprintf("%-3s %-28s\n", "QTY", "ITEM DETAILS"));
            $printer->text(str_repeat("-", 32) . "\n");

            // --- ITEMS ---
            foreach ($ticketItems as $item) {
                $itemStatus = is_object($item) ? $item->item_status : null;
                $itemQty    = is_object($item) ? (int)$item->quantity : (int)$item['quantity'];
                $prodName   = is_object($item) ? $item->product_name : $item['product_name'];
                $actionType = is_object($item) ? $item->action_type : $item['action_type'];
                $note       = is_object($item) ? $item->order_note : ($item['order_note'] ?? null);

                if ($itemStatus === 'Cancelled' && !$isCancellation) {
                    continue;
                }
                
                $printer->setEmphasis(true);
                
                $prefix = in_array($actionType, ['Add', 'Refire', 'Cancel', 'Reduce']) 
                    ? '[' . strtoupper($actionType) . '] ' 
                    : '';

                $cleanProductName = strtoupper(substr($prefix . $prodName, 0, 27));
                
                $printer->text(sprintf("%-3d %-28s\n", $itemQty, $cleanProductName));
                $printer->setEmphasis(false);

                if (!empty($note)) {
                    $cleanNote = strtoupper(substr($note, 0, 24));
                    $printer->text("    > NOTE: " . $cleanNote . "\n");
                }
            }

            // --- FOOTER ---
            $printer->text(str_repeat("-", 32) . "\n");
            $routeNameText = "*** " . strtoupper($route->kitchen_route_name ?? 'KITCHEN') . " ***";
            $printer->text($this->centerTextEscPos($routeNameText, 32) . "\n\n");
            
            $printer->feed(2);
            $printer->cut();
            $printer->close();

            DB::table('kitchen_ticket')
                ->where('id', $ticketId)
                ->update([
                    'print_count' => DB::raw('print_count + 1'),
                    'last_printed_at' => now(),
                ]);

        } catch (\Exception $e) {
            \Log::error("Direct print failed for Route ID {$routeId}: " . $e->getMessage());
        }
    }

    private function centerTextEscPos(string $text, int $width = 32): string 
    {
        $pad = max(0, floor(($width - strlen($text)) / 2));
        return str_repeat(" ", (int)$pad) . $text;
    }

    public function toggleKitchenItemStatus(Request $request)
    {
        $ticketItemId = $request->input('ticket_item_id');

        // 1. START THE TRANSACTION IMMEDIATELY AT THE TOP
        DB::beginTransaction();

        try {
            // 2. Find the exact row and lock it for update to prevent concurrent race conditions
            $baseItem = DB::table('kitchen_ticket_item')
                ->where('id', $ticketItemId)
                ->lockForUpdate()
                ->first();

            if (!$baseItem) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Item not found.']);
            }

            $ticketId = $baseItem->kitchen_ticket_id;

            // Calculate actual remaining quantity for this specific product record line
            $groupedItems = DB::table('kitchen_ticket_item')
                ->where('kitchen_ticket_id', $ticketId)
                ->where('shop_order_item_id', $baseItem->shop_order_item_id)
                ->get();

            $baseQty   = $groupedItems->where('action_type', 'New')->sum('quantity');
            $addQty    = $groupedItems->where('action_type', 'Add')->sum('quantity');
            $reduceQty = $groupedItems->where('action_type', 'Reduce')->sum('quantity');
            $cancelQty = $groupedItems->where('action_type', 'Cancel')->sum('quantity');
            
            $remainingQty = max(($baseQty + $addQty) - ($reduceQty + $cancelQty), 0);

            /*
            |--------------------------------------------------------------------------
            | HANDLE CANCELLATIONS / STRUCTURAL VOIDS
            |--------------------------------------------------------------------------
            */
            if ($remainingQty <= 0 || $baseItem->item_status === 'Cancelled') {
                DB::table('kitchen_ticket_item')
                    ->where('id', $ticketItemId)
                    ->update([
                        'item_status' => 'Cancelled',
                        'cancelled_at' => now(),
                        'updated_at' => now()
                    ]);

                $this->recalculateTicketStatus($ticketId);
                
                DB::commit(); // Commit the transactional lock state cleanly
                
                return response()->json([
                    'success' => true, 
                    'message' => 'Item has been cancelled and skipped from inventory processing.'
                ]);
            }

            // 3. Advance the status cycle strictly for THIS database record line
            $currentStatus = $baseItem->item_status;

            // Workflow matching line progression: Queued -> Preparing -> Ready -> Served
            if ($currentStatus === 'Queued') {
                $next = 'Preparing';
            } elseif ($currentStatus === 'Preparing') {
                $next = 'Ready';
            } else {
                $next = 'Served'; 
            }

            /*
            |--------------------------------------------------------------------------
            | INTERCEPT STATUS SWAP: RUN INVENTORY ENGINE ON 'PREPARING'
            |--------------------------------------------------------------------------
            */
            if ($currentStatus === 'Queued' && $next === 'Preparing') {
                
                // Trace the source Shop Order from the item row record
                $shopOrderItem = DB::table('shop_order_item')
                    ->where('id', $baseItem->shop_order_item_id)
                    ->first();
                
                if ($shopOrderItem) {
                    $shopOrder = ShopOrder::with(['shopRegister'])->find($shopOrderItem->shop_order_id);
                    
                    if ($shopOrder) {
                        // Resolve warehouse scope assignments
                        $warehouseIds = DB::table('shop_register_warehouse')
                            ->where('shop_register_id', $shopOrder->shop_register_id)
                            ->pluck('warehouse_id')
                            ->toArray();

                        if (empty($warehouseIds)) {
                            throw new \Exception('No warehouse assigned to the register serving this order.');
                        }

                        // Locate baseline Master Inventory tracking scheme
                        $product = Product::find($shopOrderItem->product_id);

                        if ($product) {
                            // 🌟 REVISED: Pass directly into our unified recursive engine.
                            // This single line handles Standalone items, Standard BOMs, and Nested "BOM of BOM" items.
                            $this->deductInventory(
                                product: $product,
                                quantity: (float) $remainingQty,
                                referenceNumber: $shopOrder->order_number,
                                warehouseIds: $warehouseIds,
                                remarks: 'KDS - Production line component consumption'
                            );
                        }
                    }
                }
            }

            // 4. Update the exact database row entry
            DB::table('kitchen_ticket_item')
                ->where('id', $ticketItemId)
                ->update([
                    'item_status' => $next,
                    'started_at' => $next === 'Preparing' ? now() : DB::raw('started_at'),
                    'ready_at'   => $next === 'Ready'     ? now() : DB::raw('ready_at'),
                    'served_at'  => $next === 'Served'    ? now() : DB::raw('served_at'),
                    'updated_at' => now()
                ]);

            // 5. Recalculate master ticket status card
            $this->recalculateTicketStatus($ticketId);

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false, 
                'message' => 'Inventory Error: ' . $e->getMessage()
            ], 422);
        }
    }

    private function deductInventory(Product $product, float $quantity, string $referenceNumber, array $warehouseIds, ?string $remarks = null)
    {
        // Case A: Product tracks inventory directly itself
        if ($product->track_inventory === 'Yes') {
            $this->executeStockDeduction($product, $quantity, $referenceNumber, $warehouseIds, $remarks);
            return;
        }

        // Case B: Does not track inventory — resolve recipe/components down the tree recursion
        $this->deductBomComponents($product->id, $quantity, $referenceNumber, $warehouseIds, $remarks);
    }
    
    private function deductBomComponents(int $productId, float $parentQuantity, string $referenceNumber, array $warehouseIds, ?string $remarks = null)
    {
        // Fetch immediate child items for the current item configuration level
        $bomItems = DB::table('product_bom')
            ->where('product_id', $productId)
            ->get();

        // Structural safeguard: if item doesn't track inventory and has no child recipe, drop out gracefully
        if ($bomItems->isEmpty()) {
            return;
        }

        foreach ($bomItems as $bomItem) {
            // Compound multiplier calculation (Parent ordered balance * Child formula configuration requirement)
            $requiredQuantity = $parentQuantity * $bomItem->quantity;

            $component = Product::find($bomItem->bom_product_id);
            
            if (!$component) {
                throw new \Exception("BOM Ingredient ID {$bomItem->bom_product_id} missing from catalog definition.");
            }

            if ($component->track_inventory === 'Yes') {
                // Base physical raw material hit — execute direct single table record stock subtraction
                $this->executeStockDeduction($component, $requiredQuantity, $referenceNumber, $warehouseIds, $remarks);
            } else {
                // Nested sub-BOM tier found (e.g. Set Combo -> Burger -> Patty) — drill down further recursively
                $this->deductBomComponents($component->id, $requiredQuantity, $referenceNumber, $warehouseIds, $remarks);
            }
        }
    }
    
    private function executeStockDeduction(Product $product, float $quantity, string $referenceNumber, array $warehouseIds, ?string $remarks = null)
    {
        $query = StockLevel::query()
            ->with('inventoryLot')
            ->where('product_id', $product->id)
            ->whereIn('warehouse_id', $warehouseIds)
            ->where('quantity', '>', 0);

        $flowStrategy = $product->inventory_flow ?? 'FIFO';

        switch ($flowStrategy) {
            case 'LIFO':
                $query->orderBy('stock_level.id', 'desc');
                break;

            case 'FEFO':
                $query->join('inventory_lot', 'stock_level.inventory_lot_id', '=', 'inventory_lot.id')
                    ->select('stock_level.*') 
                    ->orderBy('inventory_lot.expiration_date', 'asc')
                    ->orderBy('stock_level.id', 'asc'); 
                break;

            case 'FIFO':
            case 'Manual':
            default:
                $query->orderBy('stock_level.id', 'asc');
                break;
        }

        $stocks = $query->get();

        if ($stocks->isEmpty()) {
            throw new \Exception("No stock found for: {$product->product_name} using {$flowStrategy} strategy.");
        }

        $remaining = $quantity;

        foreach ($stocks as $stock) {
            if ($remaining <= 0) {
                break;
            }

            $deduct = min($remaining, $stock->quantity);

            if ($deduct <= 0) {
                continue;
            }

            // Decrement using atomic operational logic
            $stock->decrement('quantity', $deduct);
            $stock->refresh();

            // Dynamically re-evaluate stock threshold statuses
            $stock->update([
                'stock_status' => match (true) {
                    $stock->quantity <= 0 => 'Out of Stock',
                    $stock->quantity <= ($product->reorder_level ?? 0) => 'Low Stock',
                    default => 'In Stock',
                },
            ]);

            // Fallback context remarks check
            if (empty($remarks)) {
                $remarks = (request()->is('*kitchen-ticket*') || request()->is('*toggle-item-status*'))
                    ? 'KDS - Item production started'
                    : 'POS checkout payment deduction';
            }

            $finalRemarks = sprintf("%s (%s)", $remarks, $flowStrategy);

            StockMovement::create([
                'product_id' => $product->id,
                'product_name' => $product->product_name,
                'warehouse_id' => $stock->warehouse_id,
                'warehouse_name' => $stock->warehouse_name ?? 'Unknown Warehouse',
                'inventory_lot_id' => $stock->inventory_lot_id,
                'movement_type' => 'SALE',
                'quantity' => $deduct,
                'reference_type' => 'Shop Order',
                'reference_number' => $referenceNumber,
                'remarks' => $finalRemarks,
                'last_log_by' => Auth::id() ?? 1,
            ]);

            $remaining -= $deduct;
        }

        if ($remaining > 0) {
            throw new \Exception("Insufficient stock for {$product->product_name}. Short by {$remaining} units under policy {$flowStrategy}.");
        }
    }

    private function recalculateTicketStatus($ticketId)
    {
        // 1. Fetch all individual database rows belonging to this ticket
        $items = DB::table('kitchen_ticket_item')
            ->where('kitchen_ticket_id', $ticketId)
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        $totalActiveItems = 0;
        $completedItems = 0;
        $readyItems = 0;
        $preparingItems = 0;
        $queuedItems = 0;

        foreach ($items as $item) {
            // Skip items that have been voided/cancelled by the cashier
            if ($item->item_status === 'Cancelled') {
                continue; 
            }

            // Count this as a valid row that needs to be tracked
            $totalActiveItems++;

            // Evaluate the status of this exact database line row entry
            if ($item->item_status === 'Served') {
                $completedItems++;
            } elseif ($item->item_status === 'Queued') {
                $queuedItems++;
            } elseif ($item->item_status === 'Preparing') {
                $preparingItems++;
            } elseif ($item->item_status === 'Ready') {
                $readyItems++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DETERMINING MASTER TICKET STATUS (ROW-LEVEL HIERARCHY)
        |--------------------------------------------------------------------------
        | The master ticket state drops down to match the least progressive active row.
        */
        if ($totalActiveItems === 0 || $completedItems === $totalActiveItems) {
            $ticketStatus = 'Completed';
        } elseif ($queuedItems > 0) {
            // If any single line item on the ticket is still Queued, the ticket stays Queued
            $ticketStatus = 'Queued';
        } elseif ($preparingItems > 0) {
            // If nothing is Queued, but something is still Preparing, the ticket is Preparing
            $ticketStatus = 'Preparing';
        } elseif ($readyItems > 0) {
            // If everything is done prepping and some lines are Ready, the ticket is Ready
            $ticketStatus = 'Ready';
        } else {
            $ticketStatus = 'Queued';
        }

        // Update the master kitchen ticket safely
        DB::table('kitchen_ticket')
            ->where('id', $ticketId)
            ->update([
                'ticket_status' => $ticketStatus,
                'started_at'   => $ticketStatus === 'Preparing' && null === DB::table('kitchen_ticket')->where('id', $ticketId)->value('started_at') ? now() : DB::raw('started_at'),
                'ready_at'     => $ticketStatus === 'Ready' ? now() : DB::raw('ready_at'),
                'completed_at' => $ticketStatus === 'Completed' ? now() : DB::raw('completed_at'),
            ]);
    }

    private function mapKitchenStatus($actionType)
    {
        return match ($actionType) {
            'New'    => 'Preparing',
            'Add'    => 'Preparing',
            'Reduce' => 'Preparing',
            'Cancel' => 'Cancelled',
            'Refire' => 'Preparing',
            default  => 'Pending',
        };
    }

    public function generateKitchenSendData(Request $request)
    {
        $shopOrderId = $request->input('shop_order_id');

        if (!$shopOrderId) {
            return response()->json([
                'success' => false,
                'message' => 'Missing order ID',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ORDER ITEMS WITH DEFAULT PRODUCT ROUTING
        |--------------------------------------------------------------------------
        | We join the product master table to fetch the default station routing parameters.
        */
        $items = DB::table('shop_order_item as soi')
            ->join('product as p', 'p.id', '=', 'soi.product_id')
            ->where('soi.shop_order_id', $shopOrderId)
            ->select([
                'soi.id', 
                'soi.product_id', 
                'soi.product_name', 
                'soi.quantity', 
                'soi.order_note', 
                'soi.item_status',
                'p.kitchen_route_id as default_route_id' // Extracted fallback route parameter
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | KITCHEN HISTORY & STATUS TRACKING
        |--------------------------------------------------------------------------
        | Unchanged, tracking historical production runs.
        */
        $kitchenHistory = DB::table('kitchen_ticket_item as kti')
            ->join('kitchen_ticket as kt', 'kt.id', '=', 'kti.kitchen_ticket_id')
            ->where('kt.shop_order_id', $shopOrderId)
            ->select([
                'kti.shop_order_item_id',
                'kti.action_type',
                'kti.quantity',
                'kti.item_status',
                'kt.kitchen_route_id',
                'kt.kitchen_route_name',
                'kti.id as ticket_item_id'
            ])
            ->orderBy('kti.id')
            ->get()
            ->groupBy('shop_order_item_id');

        /*
        |--------------------------------------------------------------------------
        | AVAILABLE ROUTES
        |--------------------------------------------------------------------------
        */
        $routes = DB::table('kitchen_route')
            ->select(['id', 'kitchen_route_name'])
            ->orderBy('kitchen_route_name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | BUILD RESPONSE
        |--------------------------------------------------------------------------
        */
        $response = $items->map(function ($item) use ($kitchenHistory) {
            $history = $kitchenHistory->get($item->id, collect());

            $kitchenQty = $history
                ->whereIn('item_status', ['Queued', 'Preparing', 'Ready', 'Served', 'Completed'])
                ->sum('quantity');
                
            $currentQty = (float) $item->quantity;

            $latestRoute = $history->whereNotNull('kitchen_route_id')->last();
            $lockedRouteId = $latestRoute?->kitchen_route_id;
            $lockedRouteName = $latestRoute?->kitchen_route_name;

            // Base properties template mapping dictionary helper
            $basePayload = [
                'shop_order_item_id' => $item->id,
                'product_id'        => $item->product_id,
                'product_name'      => $item->product_name,
                'order_note'        => $item->order_note,
                'status'            => $item->item_status,
                'default_route_id'  => $item->default_route_id, // Attached payload fallback value
                'previous_sent_qty' => $kitchenQty,
            ];

            /* Case 1: Cancelled */
            if ($item->item_status === 'Cancelled') {
                if ($kitchenQty > 0) {
                    return array_merge($basePayload, [
                        'quantity'          => $kitchenQty,
                        'action_type'       => 'Cancel',
                        'is_route_locked'   => !is_null($lockedRouteId),
                        'locked_route_id'   => $lockedRouteId,
                        'locked_route_name' => $lockedRouteName,
                    ]);
                }
                return null;
            }

            if ($currentQty == $kitchenQty) {
                return null;
            }

            /* Case 3: New Ticket Line Item */
            if ($kitchenQty == 0 && $currentQty > 0) {
                return array_merge($basePayload, [
                    'quantity'          => $currentQty,
                    'action_type'       => 'New',
                    'is_route_locked'   => false,
                    'locked_route_id'   => null,
                    'locked_route_name' => null,
                ]);
            }

            /* Case 4: Added Qty */
            if ($currentQty > $kitchenQty) {
                return array_merge($basePayload, [
                    'quantity'          => $currentQty - $kitchenQty,
                    'action_type'       => 'Add',
                    'is_route_locked'   => !is_null($lockedRouteId),
                    'locked_route_id'   => $lockedRouteId,
                    'locked_route_name' => $lockedRouteName,
                ]);
            }

            /* Case 5: Reduced Qty */
            if ($currentQty < $kitchenQty) {
                return array_merge($basePayload, [
                    'quantity'          => $kitchenQty - $currentQty,
                    'action_type'       => 'Reduce',
                    'is_route_locked'   => !is_null($lockedRouteId),
                    'locked_route_id'   => $lockedRouteId,
                    'locked_route_name' => $lockedRouteName,
                ]);
            }

            return null;
        })
        ->filter()
        ->values();

        return response()->json([
            'success' => true,
            'data'    => $response,
            'routes'  => $routes,
        ]);
    }

    public function generateKitchenRoutes(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        // Scope to today (Start of day to End of day in App Timezone)
        $startDate = now()->startOfDay();
        $endDate   = now()->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | 1. FETCH ROUTES
        |--------------------------------------------------------------------------
        */
        $routes = DB::table('kitchen_route')
            ->orderBy('kitchen_route_name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 2. BULK AGGREGATE TICKET & ITEM QUANTITIES
        |--------------------------------------------------------------------------
        */
        $routeStats = DB::table('kitchen_ticket as kt')
            ->leftJoin('kitchen_ticket_item as kti', 'kt.id', '=', 'kti.kitchen_ticket_id')
            ->whereBetween('kt.created_at', [$startDate, $endDate]) // 📅 Flexible date range
            ->selectRaw("
                kt.kitchen_route_id,
                COUNT(DISTINCT CASE WHEN LOWER(kt.ticket_status) IN ('queued', 'preparing', 'ready') THEN kt.id END) as active_ticket_count,
                COALESCE(SUM(CASE WHEN LOWER(kti.item_status) = 'queued' THEN kti.quantity ELSE 0 END), 0) as queued_qty,
                COALESCE(SUM(CASE WHEN LOWER(kti.item_status) = 'preparing' THEN kti.quantity ELSE 0 END), 0) as preparing_qty,
                COALESCE(SUM(CASE WHEN LOWER(kti.item_status) = 'ready' THEN kti.quantity ELSE 0 END), 0) as ready_qty,
                COALESCE(SUM(CASE WHEN LOWER(kti.item_status) IN ('completed', 'served') THEN kti.quantity ELSE 0 END), 0) as completed_qty
            ")
            ->groupBy('kt.kitchen_route_id')
            ->get()
            ->keyBy(function ($item) {
                return (string) $item->kitchen_route_id; // Normalize key to string
            });

        /*
        |--------------------------------------------------------------------------
        | 3. BULK FETCH TIMESTAMPS FOR METRICS
        |--------------------------------------------------------------------------
        */
        $ticketTimestamps = DB::table('kitchen_ticket')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("
                kitchen_route_id,
                MAX(updated_at) as latest_update,
                MIN(CASE WHEN LOWER(ticket_status) IN ('queued', 'preparing') THEN queued_at END) as oldest_queued
            ")
            ->groupBy('kitchen_route_id')
            ->get()
            ->keyBy(function ($item) {
                return (string) $item->kitchen_route_id; // Normalize key to string
            });

        /*
        |--------------------------------------------------------------------------
        | 4. MAP TO RESPONSE PAYLOAD
        |--------------------------------------------------------------------------
        */
        $response = $routes->map(function ($route) use ($pageAppId, $pageNavigationMenuId, $routeStats, $ticketTimestamps) {
            
            $routeKey = (string) $route->id;
            $stats    = $routeStats->get($routeKey);
            $times    = $ticketTimestamps->get($routeKey);

            $link = route('apps.details', [
                'appId'            => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id'       => $route->id,
            ]);

            return [
                'id'                  => $route->id,
                'kitchen_route_name'  => $route->kitchen_route_name,
                'kitchen_route_type'  => $route->kitchen_route_type ?? 'Kitchen',
                
                'queued_count'        => (int) ($stats->queued_qty ?? 0),
                'preparing_count'     => (int) ($stats->preparing_qty ?? 0),
                'ready_count'         => (int) ($stats->ready_qty ?? 0),
                'completed_count'     => (int) ($stats->completed_qty ?? 0),
                'active_ticket_count' => (int) ($stats->active_ticket_count ?? 0),

                'oldest_ticket_time'  => $times?->oldest_queued
                    ? Carbon::parse($times->oldest_queued)->diffForHumans(now(), true)
                    : null,

                'last_activity'       => $times?->latest_update
                    ? Carbon::parse($times->latest_update)->diffForHumans()
                    : 'No activity',

                'link'                => $link,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $response,
        ]);
    }

    public function generateKitchenTickets(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'detailId' => [
                'required',
                'integer',
                Rule::exists('kitchen_route', 'id'),
            ],
            'search' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        $kitchenRouteId = (int) $validated['detailId'];
        $search = trim($validated['search'] ?? '');

        /*
        |--------------------------------------------------------------------------
        | 1. ROUTE
        |--------------------------------------------------------------------------
        */
        $route = DB::table('kitchen_route')
            ->where('id', $kitchenRouteId)
            ->first();

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Kitchen route not found.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. TICKETS
        |--------------------------------------------------------------------------
        */
        $tickets = DB::table('kitchen_ticket')
            ->join('shop_order', 'shop_order.id', '=', 'kitchen_ticket.shop_order_id')
            ->leftJoin('floor_plan', 'floor_plan.id', '=', 'shop_order.floor_plan_id')
            ->leftJoin('floor_plan_table', 'floor_plan_table.id', '=', 'shop_order.floor_plan_table_id')
            ->where('kitchen_ticket.kitchen_route_id', $kitchenRouteId)
            ->whereNotIn('kitchen_ticket.ticket_status', ['Completed', 'Cancelled'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('kitchen_ticket.ticket_number', 'like', "%{$search}%")
                        ->orWhere('kitchen_ticket.shop_register_name', 'like', "%{$search}%");
                });
            })
            ->select(
                'kitchen_ticket.*',
                'shop_order.order_type',
                'shop_order.floor_plan_name',
                'shop_order.table_number'
            )
            ->orderBy('kitchen_ticket.queued_at')
            ->get();

        if ($tickets->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. ITEMS (RAW) - JOINED TO SHOP_ORDER_ITEM FOR NOTES
        |--------------------------------------------------------------------------
        | Fixed: Joined shop_order_item to inherit the real order line notes.
        | Coalesced order_note fields to protect custom inputs if either table has them.
        */
        $ticketIds = $tickets->pluck('id');

        $ticketItems = DB::table('kitchen_ticket_item as kti')
            ->leftJoin('shop_order_item as soi', 'soi.id', '=', 'kti.shop_order_item_id')
            ->whereIn('kti.kitchen_ticket_id', $ticketIds)
            ->select(
                'kti.*',
                DB::raw('COALESCE(kti.order_note, soi.order_note, soi.order_note) as resolved_note')
            )
            ->orderBy('kti.id')
            ->get()
            ->groupBy('kitchen_ticket_id');

        /*
        |--------------------------------------------------------------------------
        | 4. STATS
        |--------------------------------------------------------------------------
        */
        $queuedCount = DB::table('kitchen_ticket_item')
            ->whereIn('kitchen_ticket_id', $ticketIds)
            ->where('item_status', 'Queued')
            ->count();

        $preparingCount = DB::table('kitchen_ticket_item')
            ->whereIn('kitchen_ticket_id', $ticketIds)
            ->where('item_status', 'Preparing')
            ->count();

        $readyCount = DB::table('kitchen_ticket_item')
            ->whereIn('kitchen_ticket_id', $ticketIds)
            ->where('item_status', 'Ready')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 5. BUILD RESPONSE
        |--------------------------------------------------------------------------
        */
        $response = [
            'route_id' => $route->id,
            'route_name' => $route->kitchen_route_name,
            'ticket_count' => $tickets->count(),
            'queued_count' => $queuedCount,
            'preparing_count' => $preparingCount,
            'ready_count' => $readyCount,

            'tickets' => $tickets->map(function ($ticket) use ($ticketItems) {

                $rawItems = $ticketItems->get($ticket->id, collect());

                $items = $rawItems->map(function ($row) {
                    return [
                        'ticket_item_id' => $row->id, 
                        'shop_order_item_id' => $row->shop_order_item_id,
                        'product_name' => $row->product_name,
                        'base_quantity' => $row->quantity,
                        'add_quantity' => $row->action_type === 'Add' ? $row->quantity : 0,
                        'reduce_quantity' => $row->action_type === 'Reduce' ? $row->quantity : 0,
                        'cancelled_quantity' => $row->action_type === 'Cancel' ? $row->quantity : 0,
                        'remaining_quantity' => $row->action_type === 'Cancel' ? 0 : $row->quantity,
                        'status' => $row->item_status,
                        'order_note' => $row->resolved_note, // 🌟 Assigns the resolved order note
                    ];
                })->values();

                $minutesWaiting = \Carbon\Carbon::parse($ticket->queued_at)->diffInMinutes(now());

                return [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'ticket_status' => $ticket->ticket_status,
                    'ticket_type' => $ticket->ticket_type,
                    'minutes_waiting' => $minutesWaiting,
                    'queued_at' => \Carbon\Carbon::parse($ticket->queued_at)->format('d M Y · h:i A'),
                    'shop_register_name' => $ticket->shop_register_name,
                    'floor_plan_name' => $ticket->floor_plan_name,
                    'table_number' => $ticket->table_number,
                    'order_type' => $ticket->order_type,
                    'items' => $items,
                ];
            })->values(),
        ];

        return response()->json([
            'success' => true,
            'data' => [$response],
        ]);
    }

}
