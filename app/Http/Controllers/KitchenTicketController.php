<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class KitchenTicketController extends Controller
{
    public function sendKitchenTicket(Request $request)
    {
        $shopOrderId = $request->input('shop_order_id');

        $items = json_decode(
            $request->input('items'),
            true
        );

        if (
            !$shopOrderId
            ||
            empty($items)
        ) {

            return response()->json([
                'success' => false,
                'message' => 'Invalid request data.'
            ]);
        }

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | ORDER
            |--------------------------------------------------------------------------
            */

            $order = DB::table('shop_order')
                ->where('id', $shopOrderId)
                ->first();

            if (!$order) {

                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.'
                ]);
            }

            $resolvedItems = [];

            foreach ($items as $item) {

                $orderItemId =
                    $item['shop_order_item_id'];

                $actionType =
                    $item['action_type'];

                /*
                |--------------------------------------------------------------------------
                | ORIGINAL ROUTE
                |--------------------------------------------------------------------------
                */

                $originalRoute = DB::table('kitchen_ticket_item as kti')

                    ->join(
                        'kitchen_ticket as kt',
                        'kt.id',
                        '=',
                        'kti.kitchen_ticket_id'
                    )

                    ->where(
                        'kti.shop_order_item_id',
                        $orderItemId
                    )

                    ->whereIn(
                        'kti.action_type',
                        ['New', 'Add', 'Refire']
                    )

                    ->orderByDesc('kti.id')

                    ->select([
                        'kt.kitchen_route_id',
                        'kt.kitchen_route_name',
                    ])

                    ->first();

                $finalRouteId = null;
                $finalRouteName = null;

                /*
                |--------------------------------------------------------------------------
                | NEW ITEMS REQUIRE MANUAL ROUTE
                |--------------------------------------------------------------------------
                */

                if ($actionType === 'New') {

                    $selectedRouteId =
                        $item['kitchen_route_id'] ?? null;

                    if (!$selectedRouteId) {

                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' =>
                                'Some new items do not have kitchen route assigned.'
                        ]);
                    }

                    $route = DB::table('kitchen_route')
                        ->where('id', $selectedRouteId)
                        ->first();

                    if (!$route) {

                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid kitchen route.'
                        ]);
                    }

                    $finalRouteId =
                        $route->id;

                    $finalRouteName =
                        $route->kitchen_route_name;
                }

                /*
                |--------------------------------------------------------------------------
                | LOCKED ROUTE ACTIONS
                |--------------------------------------------------------------------------
                */

                else {

                    if (!$originalRoute) {

                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' =>
                                'Unable to determine original route.'
                        ]);
                    }

                    $finalRouteId =
                        $originalRoute->kitchen_route_id;

                    $finalRouteName =
                        $originalRoute->kitchen_route_name;
                }

                $item['kitchen_route_id'] =
                    $finalRouteId;

                $item['kitchen_route_name'] =
                    $finalRouteName;

                $resolvedItems[] = $item;
            }

            /*
            |--------------------------------------------------------------------------
            | GROUP BY ROUTE
            |--------------------------------------------------------------------------
            */

            $grouped =
                collect($resolvedItems)
                    ->groupBy('kitchen_route_id');

            $createdTickets = [];

            foreach ($grouped as $routeId => $routeItems) {

                $routeName =
                    $routeItems->first()['kitchen_route_name'];

                /*
                |--------------------------------------------------------------------------
                | CREATE TICKET
                |--------------------------------------------------------------------------
                */

                $ticketId =
                    DB::table('kitchen_ticket')
                        ->insertGetId([

                            'ticket_number' =>
                                'KT-' . time() . rand(100, 999),

                            'shop_order_id' =>
                                $shopOrderId,

                            'shop_register_id' =>
                                $order->shop_register_id,

                            'shop_register_name' =>
                                $order->shop_register_name,

                            'kitchen_route_id' =>
                                $routeId,

                            'kitchen_route_name' =>
                                $routeName,

                            'ticket_status' =>
                                'Queued',

                            'queued_at' =>
                                now(),

                            'created_by' =>
                                auth()->id(),

                            'created_by_name' =>
                                auth()->user()->name ?? 'System',

                            'created_at' =>
                                now(),

                            'updated_at' =>
                                now(),
                        ]);

                /*
                |--------------------------------------------------------------------------
                | INSERT ITEMS
                |--------------------------------------------------------------------------
                */

                foreach ($routeItems as $item) {

                    $orderItem =
                        DB::table('shop_order_item')
                            ->where(
                                'id',
                                $item['shop_order_item_id']
                            )
                            ->first();

                    DB::table('kitchen_ticket_item')
                        ->insert([

                            'kitchen_ticket_id' =>
                                $ticketId,

                            'shop_order_item_id' =>
                                $item['shop_order_item_id'],

                            'product_id' =>
                                $orderItem->product_id,

                            'product_name' =>
                                $orderItem->product_name,

                            'action_type' =>
                                $item['action_type'],

                            'quantity' =>
                                $item['quantity'],

                            'order_note' =>
                                $item['note'] ?? null,

                            'item_status' =>
                                'Queued',

                            'queued_at' =>
                                now(),

                            'created_by' =>
                                auth()->id(),

                            'created_by_name' =>
                                auth()->user()->name ?? 'System',

                            'created_at' =>
                                now(),

                            'updated_at' =>
                                now(),
                        ]);

                    DB::table('shop_order_item')
                        ->where(
                            'id',
                            $item['shop_order_item_id']
                        )
                        ->update([

                            'item_status' =>
                                $this->mapKitchenStatus(
                                    $item['action_type']
                                ),

                            'last_log_by' =>
                                auth()->id(),

                            'updated_at' =>
                                now(),
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

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

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
}
