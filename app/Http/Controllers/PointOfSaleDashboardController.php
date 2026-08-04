<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PointOfSaleDashboardController extends Controller
{
    public function fetchDetails(Request $request)
    {
        $today = Carbon::today();

        // 1. Gross Sales (Sum of all orders today including charges, before deducting voids/cancels)
        $grossSales = DB::table('shop_order')
            ->whereDate('created_at', $today)
            ->sum('gross_total');

        // 2. Net Sales (Sum of net_total today from completed or active revenue tickets)
        $netSales = DB::table('shop_order')
            ->whereDate('created_at', $today)
            ->whereNotIn('order_status', ['Cancelled', 'Voided'])
            ->sum('net_total');

        // 3. Total Order Counts Today
        $totalOrders = DB::table('shop_order')
            ->whereDate('created_at', $today)
            ->count();

        // 4. Average Order Value (AOV) Today for Active Revenue Tickets
        $avgOrderValue = DB::table('shop_order')
            ->whereDate('created_at', $today)
            ->whereNotIn('order_status', ['Cancelled', 'Voided'])
            ->avg('net_total') ?? 0;

        return response()->json([
            'success' => true,
            'notExist' => false,
            'grossSales' => number_format($grossSales, 2, '.', ','),
            'netSales' => number_format($netSales, 2, '.', ','),
            'totalOrders' => $this->formatCount($totalOrders),
            'avgOrderValue' => number_format($avgOrderValue, 2, '.', ','),
        ]);
    }

    public function generateRecentOrdersTable(Request $request)
    {
        $rows = DB::table('shop_order')
            ->select([
                'id', 'order_number', 'customer_name', 'order_type',
                'shop_register_name', 'floor_plan_name', 'table_number',
                'order_status', 'payment_status', 'net_total', 'created_at'
            ])
            ->whereDate('created_at', Carbon::today())
            ->orderByDesc('id')
            ->get()
            ->map(function ($row) {
                // Status Badge CSS Class Configurations
                $statusBadge = match($row->order_status) {
                    'Completed' => 'badge-light-success text-success',
                    'Cancelled', 'Voided' => 'badge-light-danger text-danger',
                    'Preparing', 'Ready' => 'badge-light-warning text-warning',
                    default => 'badge-light-primary text-primary'
                };

                $paymentBadge = match($row->payment_status) {
                    'Paid' => 'badge-success',
                    'Refunded' => 'badge-warning text-dark',
                    default => 'badge-danger'
                };

                return [
                    'ORDER_INFO' => '
                        <div class="d-flex flex-column">
                            <span class="fw-bold text-gray-800 fs-6">' . $row->order_number . '</span>
                            <small class="text-muted">' . ($row->customer_name ?? 'Walk-In Customer') . ' • ' . $row->order_type . '</small>
                        </div>
                    ',
                    'LOCATION_CONTEXT' => '
                        <div class="d-flex flex-column">
                            <span class="fw-semibold text-gray-700">' . $row->shop_register_name . '</span>
                            <small class="text-muted">' . ($row->floor_plan_name ? $row->floor_plan_name . ' [Tbl ' . $row->table_number . ']' : 'Front Counter') . '</small>
                        </div>
                    ',
                    'STATUS' => '
                        <div class="d-flex flex-column gap-1">
                            <span class="badge ' . $statusBadge . ' fw-bold px-2 py-1 fs-8 w-fit">' . $row->order_status . '</span>
                            <span class="badge ' . $paymentBadge . ' text-white fw-bold px-2 py-1 fs-9 w-fit">' . $row->payment_status . '</span>
                        </div>
                    ',
                    'NET_TOTAL' => '<span class="text-gray-900 fw-bold fs-6">₱' . number_format($row->net_total, 2) . '</span>'
                ];
        });

        return $this->formatTableResponse($rows);
    }

    public function generatePaymentsLedgerTable(Request $request)
    {
        $rows = DB::table('shop_order_payment')
            ->join('shop_order', 'shop_order_payment.shop_order_id', '=', 'shop_order.id')
            ->select([
                'shop_order.order_number',
                'shop_order_payment.payment_method_name',
                'shop_order_payment.reference_number',
                'shop_order_payment.reference_name',
                'shop_order_payment.payment_status',
                'shop_order_payment.payment_amount',
                'shop_order_payment.paid_at'
            ])
            ->whereDate('shop_order_payment.created_at', Carbon::today())
            ->orderByDesc('shop_order_payment.id')
            ->get()
            ->map(function ($row) {
                $statusClass = match($row->payment_status) {
                    'Paid' => 'badge-light-success text-success',
                    'Refunded' => 'badge-light-warning text-warning',
                    default => 'badge-light-danger text-danger'
                };

                return [
                    'ORDER_REF' => '<span class="fw-bold text-gray-800">' . $row->order_number . '</span>',
                    'METHOD' => '<span class="badge badge-light-secondary text-gray-800 fw-bold">' . strtoupper($row->payment_method_name) . '</span>',
                    'TRACE_REF' => '
                        <div class="d-flex flex-column">
                            <span class="text-gray-700 font-monospace">' . ($row->reference_number ?? '-') . '</span>
                            <small class="text-muted">' . ($row->reference_name ?? '') . '</small>
                        </div>
                    ',
                    'STATUS' => '<span class="badge ' . $statusClass . ' fw-bold">' . $row->payment_status . '</span>',
                    'AMOUNT' => '<span class="fw-bold text-gray-900">₱' . number_format($row->payment_amount, 2) . '</span>'
                ];
        });

        return $this->formatTableResponse($rows);
    }

    private function formatCount($count)
    {
        if ($count >= 1000000) {
            return round($count / 1000000, 1) . 'M';
        }
        if ($count >= 1000) {
            return round($count / 1000, 1) . 'k';
        }
        return (string) $count;
    }

    private function formatTableResponse($rows)
    {
        return response()->json(
            collect($rows)->values()
        );
    }
}