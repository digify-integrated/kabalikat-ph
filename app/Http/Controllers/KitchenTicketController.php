<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

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
            $order = DB::table('shop_order')->where('id', $shopOrderId)->first();

            if (!$order) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.'
                ]);
            }

            $resolvedItems = [];

            foreach ($items as $item) {
                $orderItemId = $item['shop_order_item_id'];
                $actionType = $item['action_type'];

                $originalRoute = DB::table('kitchen_ticket_item as kti')
                    ->join('kitchen_ticket as kt', 'kt.id', '=', 'kti.kitchen_ticket_id')
                    ->where('kti.shop_order_item_id', $orderItemId)
                    ->whereIn('kti.action_type', ['New', 'Add', 'Refire', 'Reduce', 'Cancel'])
                    ->orderByDesc('kti.id')
                    ->select(['kt.kitchen_route_id', 'kt.kitchen_route_name'])
                    ->first();

                $finalRouteId = null;
                $finalRouteName = null;

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

            foreach ($grouped as $routeId => $routeItems) {
                $routeName = $routeItems->first()['kitchen_route_name'];

                $existingTicket = DB::table('kitchen_ticket')
                    ->where('shop_order_id', $shopOrderId)
                    ->where('kitchen_route_id', $routeId)
                    ->whereNotIn('ticket_status', ['Completed', 'Cancelled'])
                    ->first();

                if ($existingTicket) {
                    $ticketId = $existingTicket->id;
                } else {
                    $ticketId = DB::table('kitchen_ticket')->insertGetId([
                        'ticket_number'      => 'KT-' . time() . rand(100, 999),
                        'shop_order_id'      => $shopOrderId,
                        'shop_register_id'   => $order->shop_register_id,
                        'shop_register_name' => $order->shop_register_name,
                        'kitchen_route_id'   => $routeId,
                        'kitchen_route_name' => $routeName,
                        'ticket_status'      => 'Queued',
                        'queued_at'          => now(),
                        'created_by'         => auth()->id(),
                        'created_by_name'    => auth()->user()->name ?? 'System',
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }

                foreach ($routeItems as $item) {
                    $orderItem = DB::table('shop_order_item')->where('id', $item['shop_order_item_id'])->first();

                    DB::table('kitchen_ticket_item')->insert([
                        'kitchen_ticket_id'  => $ticketId,
                        'shop_order_item_id' => $item['shop_order_item_id'],
                        'product_id'         => $orderItem->product_id,
                        'product_name'       => $orderItem->product_name,
                        'action_type'        => $item['action_type'],
                        'quantity'           => $item['quantity'],
                        'order_note'         => $item['note'] ?? null,
                        'item_status'        => 'Queued',
                        'queued_at'          => now(),
                        'created_by'         => auth()->id(),
                        'created_by_name'    => auth()->user()->name ?? 'System',
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);

                    DB::table('shop_order_item')
                        ->where('id', $item['shop_order_item_id'])
                        ->update([
                            'item_status' => $this->mapKitchenStatus($item['action_type']),
                            'last_log_by' => auth()->id(),
                            'updated_at'  => now(),
                        ]);
                }

                /*
                |--------------------------------------------------------------------------
                |  FIX: RE-EVALUATE MASTER STATUS
                |--------------------------------------------------------------------------
                | When an extra item drops onto an active ticket, force the master entry 
                | to adjust down according to the lowest incomplete item status.
                | This pushes the card layout container visually back to "Queued".
                */
                $this->recalculateTicketStatus($ticketId);

                $createdTickets[] = $ticketId;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sent to kitchen successfully.',
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

    public function toggleKitchenItemStatus(Request $request)
    {
        $ticketItemId = $request->input('ticket_item_id');

        // 1. Find the exact row being interacted with
        $baseItem = DB::table('kitchen_ticket_item')->where('id', $ticketItemId)->first();

        if (!$baseItem) {
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

        // Handle Cancellations / Structural Voids
        if ($remainingQty <= 0 || $baseItem->item_status === 'Cancelled') {
            DB::table('kitchen_ticket_item')
                ->where('id', $ticketItemId)
                ->update([
                    'item_status' => 'Cancelled',
                    'cancelled_at' => now()
                ]);

            $this->recalculateTicketStatus($ticketId);
            return response()->json(['success' => true, 'message' => 'Item cancelled.']);
        }

        // 2. Advance the status cycle strictly for THIS database record line
        $currentStatus = $baseItem->item_status;

        // Workflow matching line progression: Queued -> Preparing -> Ready -> Served
        if ($currentStatus === 'Queued') {
            $next = 'Preparing';
        } elseif ($currentStatus === 'Preparing') {
            $next = 'Ready';
        } else {
            $next = 'Served'; 
        }

        // 3. Update the exact database row entry
        DB::table('kitchen_ticket_item')
            ->where('id', $ticketItemId)
            ->update([
                'item_status' => $next,
                'started_at' => $next === 'Preparing' ? now() : DB::raw('started_at'),
                'ready_at'   => $next === 'Ready'     ? now() : DB::raw('ready_at'),
                'served_at'  => $next === 'Served'    ? now() : DB::raw('served_at'),
            ]);

        // 4. Recalculate master ticket status card
        $this->recalculateTicketStatus($ticketId);

        return response()->json(['success' => true]);
    }

    private function recalculateTicketStatus($ticketId)
    {
        // Fetch all items belonging to this ticket
        $items = DB::table('kitchen_ticket_item')
            ->where('kitchen_ticket_id', $ticketId)
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        // Group items by shop_order_item_id to evaluate their effective remaining quantities
        $groupedByProduct = $items->groupBy('shop_order_item_id'); 

        $totalActiveGroups = 0;
        $completedGroups = 0;
        $readyGroups = 0;
        $preparingGroups = 0;
        $queuedGroups = 0;

        foreach ($groupedByProduct as $shopOrderItemId => $group) {
            $baseQty   = $group->where('action_type', 'New')->sum('quantity');
            $addQty    = $group->where('action_type', 'Add')->sum('quantity');
            $reduceQty = $group->where('action_type', 'Reduce')->sum('quantity');
            $cancelQty = $group->where('action_type', 'Cancel')->sum('quantity');
            
            $remainingQty = max(($baseQty + $addQty) - ($reduceQty + $cancelQty), 0);

            // If the product line has 0 remaining quantities, it requires NO work. Skip it.
            if ($remainingQty <= 0) {
                continue; 
            }

            $totalActiveGroups++;

            // Read active statuses (ignoring explicit cancellation entries)
            $statuses = $group->whereNotIn('item_status', ['Cancelled'])->pluck('item_status')->unique()->toArray();

            if (empty($statuses) || in_array('Served', $statuses)) {
                $completedGroups++;
            } elseif (in_array('Queued', $statuses)) {
                $queuedGroups++;
            } elseif (in_array('Preparing', $statuses)) {
                $preparingGroups++;
            } elseif (in_array('Ready', $statuses)) {
                $readyGroups++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DETERMINING MASTER TICKET STATUS (FIXED HIERARCHY)
        |--------------------------------------------------------------------------
        | The ticket status follows the least progressive active item line status.
        */
        if ($totalActiveGroups === 0 || $completedGroups === $totalActiveGroups) {
            $ticketStatus = 'Completed';
        } elseif ($queuedGroups > 0) {
            // 1. If any single item line is still Queued, the master ticket stays Queued
            $ticketStatus = 'Queued';
        } elseif ($preparingGroups > 0) {
            // 2. If nothing is Queued, but something is still Preparing, the ticket is Preparing
            $ticketStatus = 'Preparing';
        } elseif ($readyGroups > 0) {
            // 3. If everything is done prepping and some lines are Ready, the ticket is Ready
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
        | ORDER ITEMS
        |--------------------------------------------------------------------------
        */

        $items = DB::table('shop_order_item')
            ->where('shop_order_id', $shopOrderId)
            ->select([
                'id',
                'product_id',
                'product_name',
                'quantity',
                'order_note',
                'item_status',
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | KITCHEN HISTORY
        |--------------------------------------------------------------------------
        */

        $kitchenHistory = DB::table('kitchen_ticket_item as kti')
            ->join(
                'kitchen_ticket as kt',
                'kt.id',
                '=',
                'kti.kitchen_ticket_id'
            )
            ->where('kt.shop_order_id', $shopOrderId)
            ->select([
                'kti.shop_order_item_id',
                'kti.action_type',
                'kti.quantity',
                'kt.kitchen_route_id',
                'kt.kitchen_route_name',
                'kti.created_at',
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
            ->select([
                'id',
                'kitchen_route_name',
            ])
            ->orderBy('kitchen_route_name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | BUILD RESPONSE
        |--------------------------------------------------------------------------
        */

        $response = $items->map(function ($item) use ($kitchenHistory) {

            $history =
                $kitchenHistory->get(
                    $item->id,
                    collect()
                );

            /*
            |--------------------------------------------------------------------------
            | REBUILD KITCHEN QTY
            |--------------------------------------------------------------------------
            */

            $kitchenQty = 0;

            foreach ($history as $row) {

                switch ($row->action_type) {

                    case 'New':
                    case 'Add':
                    case 'Refire':

                        $kitchenQty += $row->quantity;

                        break;

                    case 'Reduce':
                    case 'Cancel':

                        $kitchenQty -= $row->quantity;

                        break;
                }
            }

            $kitchenQty =
                max(0, $kitchenQty);

            $currentQty =
                (float) $item->quantity;

            /*
            |--------------------------------------------------------------------------
            | ORIGINAL ROUTE
            |--------------------------------------------------------------------------
            */

            $latestRoute =
                $history
                    ->whereIn('action_type', [
                        'New',
                        'Add',
                        'Refire',
                    ])
                    ->last();

            $lockedRouteId =
                $latestRoute?->kitchen_route_id;

            $lockedRouteName =
                $latestRoute?->kitchen_route_name;

            /*
            |--------------------------------------------------------------------------
            | CANCELLED ITEM
            |--------------------------------------------------------------------------
            */

            if (
                $item->item_status === 'Cancelled'
            ) {

                if ($kitchenQty > 0) {

                    return [

                        'shop_order_item_id' =>
                            $item->id,

                        'product_id' =>
                            $item->product_id,

                        'product_name' =>
                            $item->product_name,

                        'quantity' =>
                            $kitchenQty,

                        'note' =>
                            $item->order_note,

                        'status' =>
                            'Cancelled',

                        'action_type' =>
                            'Cancel',

                        /*
                        |--------------------------------------------------------------------------
                        | LOCK ROUTE
                        |--------------------------------------------------------------------------
                        */

                        'is_route_locked' =>
                            true,

                        'locked_route_id' =>
                            $lockedRouteId,

                        'locked_route_name' =>
                            $lockedRouteName,

                        'previous_sent_qty' =>
                            $kitchenQty,
                    ];
                }

                return null;
            }

            /*
            |--------------------------------------------------------------------------
            | NO CHANGES
            |--------------------------------------------------------------------------
            */

            if ($currentQty == $kitchenQty) {
                return null;
            }

            /*
            |--------------------------------------------------------------------------
            | NEW ITEM
            |--------------------------------------------------------------------------
            */

            if (
                $kitchenQty == 0
                &&
                $currentQty > 0
            ) {

                return [

                    'shop_order_item_id' =>
                        $item->id,

                    'product_id' =>
                        $item->product_id,

                    'product_name' =>
                        $item->product_name,

                    'quantity' =>
                        $currentQty,

                    'note' =>
                        $item->order_note,

                    'status' =>
                        $item->item_status,

                    'action_type' =>
                        'New',

                    /*
                    |--------------------------------------------------------------------------
                    | CASHIER MAY SELECT ROUTE
                    |--------------------------------------------------------------------------
                    */

                    'is_route_locked' =>
                        false,

                    'locked_route_id' =>
                        null,

                    'locked_route_name' =>
                        null,

                    'previous_sent_qty' =>
                        0,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | ADDITIONAL QTY
            |--------------------------------------------------------------------------
            */

            if ($currentQty > $kitchenQty) {

                return [

                    'shop_order_item_id' =>
                        $item->id,

                    'product_id' =>
                        $item->product_id,

                    'product_name' =>
                        $item->product_name,

                    'quantity' =>
                        $currentQty - $kitchenQty,

                    'note' =>
                        $item->order_note,

                    'status' =>
                        $item->item_status,

                    'action_type' =>
                        'Add',

                    /*
                    |--------------------------------------------------------------------------
                    | LOCK TO ORIGINAL ROUTE
                    |--------------------------------------------------------------------------
                    */

                    'is_route_locked' =>
                        true,

                    'locked_route_id' =>
                        $lockedRouteId,

                    'locked_route_name' =>
                        $lockedRouteName,

                    'previous_sent_qty' =>
                        $kitchenQty,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | REDUCE QTY
            |--------------------------------------------------------------------------
            */

            if ($currentQty < $kitchenQty) {

                return [

                    'shop_order_item_id' =>
                        $item->id,

                    'product_id' =>
                        $item->product_id,

                    'product_name' =>
                        $item->product_name,

                    'quantity' =>
                        $kitchenQty - $currentQty,

                    'note' =>
                        $item->order_note,

                    'status' =>
                        $item->item_status,

                    'action_type' =>
                        'Reduce',

                    /*
                    |--------------------------------------------------------------------------
                    | LOCK TO ORIGINAL ROUTE
                    |--------------------------------------------------------------------------
                    */

                    'is_route_locked' =>
                        true,

                    'locked_route_id' =>
                        $lockedRouteId,

                    'locked_route_name' =>
                        $lockedRouteName,

                    'previous_sent_qty' =>
                        $kitchenQty,
                ];
            }

            return null;
        })
        ->filter()
        ->values();

        return response()->json([

            'success' => true,

            'data' => $response,

            'routes' => $routes,
        ]);
    }

    public function generateKitchenRoutes(Request $request)
    {
        $pageAppId = (int) $request->input('appId');
        $pageNavigationMenuId = (int) $request->input('navigationMenuId');

        /*
        |--------------------------------------------------------------------------
        | ROUTES
        |--------------------------------------------------------------------------
        */

        $routes = DB::table('kitchen_route')
            ->orderBy('kitchen_route_name')
            ->get();

        $response = $routes->map(function ($route) use (
            $pageAppId,
            $pageNavigationMenuId
        ) {

            /*
            |--------------------------------------------------------------------------
            | ACTIVE TICKETS
            |--------------------------------------------------------------------------
            */

            $activeTickets = DB::table('kitchen_ticket')
                ->where('kitchen_route_id', $route->id)
                ->whereIn('ticket_status', [
                    'Queued',
                    'Preparing',
                    'Ready'
                ]);

            $activeTicketCount =
                (clone $activeTickets)->count();

            /*
            |--------------------------------------------------------------------------
            | ITEM COUNTS
            |--------------------------------------------------------------------------
            */

            $itemStats = DB::table('kitchen_ticket_item as kti')

                ->join(
                    'kitchen_ticket as kt',
                    'kt.id',
                    '=',
                    'kti.kitchen_ticket_id'
                )

                ->where('kt.kitchen_route_id', $route->id)

                ->selectRaw("
                    SUM(CASE WHEN kti.item_status = 'Queued' THEN 1 ELSE 0 END) as queued_count,
                    SUM(CASE WHEN kti.item_status = 'Preparing' THEN 1 ELSE 0 END) as preparing_count,
                    SUM(CASE WHEN kti.item_status = 'Ready' THEN 1 ELSE 0 END) as ready_count,
                    SUM(CASE WHEN kti.item_status = 'Completed' THEN 1 ELSE 0 END) as completed_count
                ")

                ->first();

            /*
            |--------------------------------------------------------------------------
            | OLDEST ACTIVE TICKET
            |--------------------------------------------------------------------------
            */

            $oldestTicket = DB::table('kitchen_ticket')
                ->where('kitchen_route_id', $route->id)
                ->whereIn('ticket_status', [
                    'Queued',
                    'Preparing',
                ])
                ->oldest('queued_at')
                ->first();

            /*
            |--------------------------------------------------------------------------
            | LAST ACTIVITY
            |--------------------------------------------------------------------------
            */

            $latestActivity = DB::table('kitchen_ticket')
                ->where('kitchen_route_id', $route->id)
                ->latest('updated_at')
                ->first();

            /*
            |--------------------------------------------------------------------------
            | LINK
            |--------------------------------------------------------------------------
            */

            $link = route('apps.details', [
                'appId' => $pageAppId,
                'navigationMenuId' => $pageNavigationMenuId,
                'details_id' => $route->id,
            ]);

            return [

                'id' => $route->id,

                'kitchen_route_name' =>
                    $route->kitchen_route_name,

                'kitchen_route_type' =>
                    $route->kitchen_route_type ?? 'Kitchen',

                'queued_count' =>
                    (int) ($itemStats->queued_count ?? 0),

                'preparing_count' =>
                    (int) ($itemStats->preparing_count ?? 0),

                'ready_count' =>
                    (int) ($itemStats->ready_count ?? 0),

                'completed_count' =>
                    (int) ($itemStats->completed_count ?? 0),

                'active_ticket_count' =>
                    $activeTicketCount,

                'oldest_ticket_time' =>
                    $oldestTicket
                        ? Carbon::parse($oldestTicket->queued_at)
                            ->diffForHumans(now(), true)
                        : null,

                'last_activity' =>
                    $latestActivity
                        ? Carbon::parse($latestActivity->updated_at)
                            ->diffForHumans()
                        : 'No activity',

                'link' => $link,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $response,
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
        | ROUTE
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
        | TICKETS
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
        | ITEMS (RAW)
        |--------------------------------------------------------------------------
        */

        $ticketIds = $tickets->pluck('id');

        $ticketItems = DB::table('kitchen_ticket_item')
            ->whereIn('kitchen_ticket_id', $ticketIds)
            ->orderBy('id')
            ->get()
            ->groupBy('kitchen_ticket_id');

        /*
        |--------------------------------------------------------------------------
        | STATS
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
        | BUILD RESPONSE
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

                /*
                |--------------------------------------------------------------------------
                | FINAL GROUPED ITEMS (UPDATED SEPARATION)
                |--------------------------------------------------------------------------
                */
                $items = $rawItems
                    ->groupBy('id') // Change grouping from shop_order_item_id to unique id
                    ->map(function ($group) {
                        $first = $group->first();

                        return [
                            'ticket_item_id' => $first->id,
                            'shop_order_item_id' => $first->shop_order_item_id,
                            'product_name' => $first->product_name,
                            'base_quantity' => $first->quantity, // Treat quantities natively relative to row entries
                            'add_quantity' => $first->action_type === 'Add' ? $first->quantity : 0,
                            'reduce_quantity' => $first->action_type === 'Reduce' ? $first->quantity : 0,
                            'cancelled_quantity' => $first->action_type === 'Cancel' ? $first->quantity : 0,
                            'remaining_quantity' => $first->action_type === 'Cancel' ? 0 : $first->quantity,
                            'status' => $first->item_status,
                            'note' => $first->order_note,
                        ];
                    })
                    ->values();

                /*
                |--------------------------------------------------------------------------
                | WAIT TIME
                |--------------------------------------------------------------------------
                */

                $minutesWaiting = Carbon::parse($ticket->queued_at)
                    ->diffInMinutes(now());

                return [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'ticket_status' => $ticket->ticket_status,
                    'ticket_type' => $ticket->ticket_type,

                    'minutes_waiting' => $minutesWaiting,

                    'queued_at' => Carbon::parse($ticket->queued_at)
                        ->format('d M Y · h:i A'),

                    'shop_register_name' => $ticket->shop_register_name,
                    'floor_plan_name' => $ticket->floor_plan_name,
                    'table_number' => $ticket->table_number,
                    'order_type' => $ticket->order_type,

                    /*
                    |--------------------------------------------------------------------------
                    | FINAL GROUPED ITEMS (IMPORTANT)
                    |--------------------------------------------------------------------------
                    */

                    'items' => $items->values(),
                ];
            })->values(),
        ];

        return response()->json([
            'success' => true,
            'data' => [$response],
        ]);
    }

}
