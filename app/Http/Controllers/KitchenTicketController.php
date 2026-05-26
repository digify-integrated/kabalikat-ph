<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class KitchenTicketController extends Controller
{
    public function sendKitchenTicket(Request $request)
    {
        \Log::info('KITCHEN SEND RAW REQUEST', $request->all());

        $shopOrderId = $request->input('shop_order_id');
        $kitchenRouteId = $request->input('kitchen_route_id');

        $items = json_decode($request->input('items'), true);

        if (!$shopOrderId || empty($items)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data.'
            ]);
        }

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | FIX STRUCTURE
            |--------------------------------------------------------------------------
            */

            $items = array_map(function ($item) use ($kitchenRouteId) {

                $item['kitchen_route_id'] = $kitchenRouteId;

                return $item;

            }, $items);

            $grouped = collect($items)->groupBy('kitchen_route_id');

            $createdTickets = [];

            foreach ($grouped as $routeId => $routeItems) {

                $route = DB::table('kitchen_route')
                    ->where('id', $routeId)
                    ->first();

                if (!$route) continue;

                $order = DB::table('shop_order')
                    ->where('id', $shopOrderId)
                    ->first();

                $ticketId = DB::table('kitchen_ticket')->insertGetId([
                    'ticket_number'      => 'KT-' . time() . rand(100, 999),
                    'shop_order_id'      => $shopOrderId,
                    'shop_register_id'   => $order->shop_register_id,
                    'shop_register_name' => $order->shop_register_name,
                    'kitchen_route_id'   => $routeId,
                    'kitchen_route_name' => $route->kitchen_route_name,
                    'ticket_status'      => 'Queued',
                    'queued_at'          => now(),
                    'created_by'         => auth()->id(),
                    'created_by_name'    => auth()->user()->name ?? 'System',
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);

                foreach ($routeItems as $item) {
                    $orderItem = DB::table('shop_order_item')
                        ->where('id', $item['shop_order_item_id'])
                        ->first();

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

                $createdTickets[] = $ticketId;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sent to kitchen successfully.',
                'tickets' => $createdTickets
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
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
            ->join('kitchen_ticket as kt', 'kt.id', '=', 'kti.kitchen_ticket_id')
            ->where('kt.shop_order_id', $shopOrderId)
            ->select([
                'kti.shop_order_item_id',
                'kti.action_type',
                'kti.quantity',
            ])
            ->get()
            ->groupBy('shop_order_item_id');

        /*
        |--------------------------------------------------------------------------
        | BUILD DELTA RESPONSE
        |--------------------------------------------------------------------------
        */

        $response = $items->map(function ($item) use ($kitchenHistory) {

            $history = $kitchenHistory->get($item->id, collect());

            /*
            |--------------------------------------------------------------------------
            | REBUILD KITCHEN QTY FROM HISTORY
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

            $kitchenQty = max(0, $kitchenQty);
            $currentQty = (float) $item->quantity;

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT: HANDLE CANCELLED ITEM FIRST
            |--------------------------------------------------------------------------
            */

            if ($item->item_status === 'Cancelled') {

                if ($kitchenQty > 0) {

                    return [
                        'shop_order_item_id' => $item->id,
                        'product_id'         => $item->product_id,
                        'product_name'       => $item->product_name,
                        'quantity'           => $kitchenQty,
                        'note'               => $item->order_note,
                        'status'             => 'Cancelled',
                        'action_type'        => 'Cancel',
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

            if ($kitchenQty == 0 && $currentQty > 0) {

                return [
                    'shop_order_item_id' => $item->id,
                    'product_id'         => $item->product_id,
                    'product_name'       => $item->product_name,
                    'quantity'           => $currentQty,
                    'note'               => $item->order_note,
                    'status'             => $item->item_status,
                    'action_type'        => 'New',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | ADD QTY
            |--------------------------------------------------------------------------
            */

            if ($currentQty > $kitchenQty) {

                return [
                    'shop_order_item_id' => $item->id,
                    'product_id'         => $item->product_id,
                    'product_name'       => $item->product_name,
                    'quantity'           => $currentQty - $kitchenQty,
                    'note'               => $item->order_note,
                    'status'             => $item->item_status,
                    'action_type'        => 'Add',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | REDUCE QTY
            |--------------------------------------------------------------------------
            */

            if ($currentQty < $kitchenQty) {

                return [
                    'shop_order_item_id' => $item->id,
                    'product_id'         => $item->product_id,
                    'product_name'       => $item->product_name,
                    'quantity'           => $kitchenQty - $currentQty,
                    'note'               => $item->order_note,
                    'status'             => $item->item_status,
                    'action_type'        => 'Reduce',
                ];
            }

            return null;
        })
        ->filter()
        ->values();

        return response()->json([
            'success' => true,
            'data'    => $response,
        ]);
    }
}
