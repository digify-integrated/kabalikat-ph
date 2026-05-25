<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopOrderReportController extends Controller
{
    public function generateCashCountReportTable(Request $request)
    {
        $filterRegister = $request->input('filter_register');
        $filterCashier = $request->input('filter_cashier');
        $filterVarianceStatus = $request->input('filter_variance_status');
        $filterDate = $request->input('filter_session_date');

        $parseRange = function ($range) {
            if (!$range) {
                return null;
            }

            $dates = explode(' - ', $range);

            if (count($dates) !== 2) {
                return null;
            }

            return [
                Carbon::createFromFormat('m/d/Y', trim($dates[0]))->startOfDay(),
                Carbon::createFromFormat('m/d/Y', trim($dates[1]))->endOfDay(),
            ];
        };

        $dateRange = $parseRange($filterDate);

        $sessions = DB::table('shop_register_session as s')
            ->select([
                's.*',
            ])
            ->when(!empty($filterRegister), fn($q) =>
                $q->whereIn('s.shop_register_id', (array) $filterRegister)
            )
            ->when(!empty($filterCashier), fn($q) =>
                $q->whereIn('s.open_user_account_id', (array) $filterCashier)
            )
            ->when($dateRange, fn($q) =>
                $q->whereBetween('s.open_time', $dateRange)
            )
            ->orderByDesc('s.open_time')
            ->get();

        $response = $sessions->map(function ($row) use ($filterVarianceStatus) {
            $cashSales = DB::table('shop_order_payment as sop')
                ->join('shop_order as so', 'so.id', '=', 'sop.shop_order_id')
                ->where('so.shop_register_session_id', $row->id)
                ->where('sop.payment_method_id', 1)
                ->where('sop.payment_status', 'Paid')
                ->sum('sop.payment_amount');

            $expectedCash = $row->open_amount + $cashSales;
            $actualCash = (float) $row->close_amount;
            $variance = $actualCash - $expectedCash;
            $status = match (true) {
                $variance == 0 => 'Balanced',
                $variance < 0 => 'Short',
                default => 'Over',
            };

            if (
                !empty($filterVarianceStatus)
                && !in_array($status, (array) $filterVarianceStatus)
            ) {
                return null;
            }

            return [
                'REGISTER' => '
                    <div class="d-flex flex-column">
                        <span class="fw-bold">'.$row->shop_register_name.'</span>
                    </div>
                ',
                'CASHIER' => '
                    <div class="d-flex flex-column">
                        <span class="fw-bold">'.$row->open_user_name.'</span>
                    </div>
                ',
                'SESSION' => '
                    <div class="d-flex flex-column">
                        <span class="fw-bold">#'.$row->id.'</span>
                    </div>
                ',
                'OPENING_CASH' => '
                    <span class="fw-bold text-primary">
                        '.number_format($row->open_amount, 2).'
                    </span>
                ',
                'CASH_SALES' => '
                    <span class="fw-bold text-success">
                        '.number_format($cashSales, 2).'
                    </span>
                ',
                'EXPECTED_CASH' => '
                    <span class="fw-bold">
                        '.number_format($expectedCash, 2).'
                    </span>
                ',
                'ACTUAL_CASH' => '
                    <span class="fw-bold">
                        '.number_format($actualCash, 2).'
                    </span>
                ',
                'VARIANCE' => '
                    <span class="fw-bold '.(
                        $variance < 0
                            ? 'text-danger'
                            : (
                                $variance > 0
                                    ? 'text-warning'
                                    : 'text-success'
                            )
                    ).'">
                        '.number_format($variance, 2).'
                    </span>
                ',
                'STATUS' => '
                    <span class="badge badge-light-'.(
                        $status === 'Balanced'
                            ? 'success'
                            : (
                                $status === 'Short'
                                    ? 'danger'
                                    : 'warning'
                            )
                    ).'">
                        '.$status.'
                    </span>
                ',
                'OPENED' => '
                    <div class="d-flex flex-column">
                        <span>'.Carbon::parse($row->open_time)->format('M d, Y').'</span>
                        <small class="text-muted">'.Carbon::parse($row->open_time)->format('h:i A').'</small>
                    </div>
                ',
                'CLOSED' => '
                    <div class="d-flex flex-column">
                        <span>'.optional($row->close_time ? Carbon::parse($row->close_time) : null)?->format('M d, Y').'</span>
                        <small class="text-muted">'.optional($row->close_time ? Carbon::parse($row->close_time) : null)?->format('h:i A').'</small>
                    </div>
                ',
            ];
        })
        ->filter()
        ->values();

        return response()->json($response);
    }

    public function generateTransactionSummaryTable(Request $request)
    {
        $filterByOrderType = $request->input('filter_order_type');
        $filterByOrderStatus = $request->input('filter_order_status');
        $filterByPaymentStatus = $request->input('filter_payment_status');
        $filterByRegister = $request->input('filter_register');
        $filterByCashier = $request->input('filter_cashier');
        $filterByDate = $request->input('filter_transaction_date');

        $parseRange = function ($range) {
            if (!$range) return null;

            $dates = explode(' - ', $range);

            if (count($dates) !== 2) return null;

            return [
                Carbon::createFromFormat('m/d/Y', trim($dates[0]))->startOfDay(),
                Carbon::createFromFormat('m/d/Y', trim($dates[1]))->endOfDay(),
            ];
        };

        $dateRange = $parseRange($filterByDate);

        $transactions = DB::table('shop_order')
            ->when($filterByOrderType, fn($q) =>
                $q->whereIn('order_type', (array) $filterByOrderType)
            )
            ->when($filterByOrderStatus, fn($q) =>
                $q->whereIn('order_status', (array) $filterByOrderStatus)
            )
            ->when($filterByPaymentStatus, fn($q) =>
                $q->whereIn('payment_status', (array) $filterByPaymentStatus)
            )
            ->when($filterByRegister, fn($q) =>
                $q->whereIn('shop_register_id', (array) $filterByRegister)
            )
            ->when($filterByCashier, fn($q) =>
                $q->whereIn('created_by', (array) $filterByCashier)
            )
            ->when($dateRange, fn($q) =>
                $q->whereBetween('ordered_at', $dateRange)
            )
            ->orderByDesc('ordered_at')
            ->get();

        return $transactions->map(function ($row) {
            $orderStatusBadge = match ($row->order_status) {
                'Pending'   => 'badge-light-warning',
                'Preparing' => 'badge-light-primary',
                'Ready'     => 'badge-light-info',
                'Completed' => 'badge-light-success',
                'Cancelled' => 'badge-light-danger',
                'Voided'    => 'badge-light-danger',
                default     => 'badge-light-secondary',
            };

            $paymentStatusBadge = match ($row->payment_status) {
                'Unpaid'   => 'badge-light-danger',
                'Paid'     => 'badge-light-success',
                'Refunded' => 'badge-light-danger',
                default    => 'badge-light-secondary',
            };
            
            return [
                'ORDER_NO' => $row->order_number,
                'REGISTER' => $row->shop_register_name,
                'CASHIER' => $row->created_by_name,
                'ORDER_TYPE' => $row->order_type,
                'ORDER_STATUS' => '
                    <span class="badge '.$orderStatusBadge.'">
                        '.$row->order_status.'
                    </span>
                ',
                'PAYMENT_STATUS' => '
                    <span class="badge '.$paymentStatusBadge.'">
                        '.$row->payment_status.'
                    </span>
                ',
                'ITEMS' => (string) $row->total_items,
                'GROSS' => number_format($row->gross_total, 2),
                'DISCOUNT' => number_format($row->discount_total, 2),
                'CHARGES' => number_format($row->charge_total, 2),
                'NET' => number_format($row->net_total, 2),
                'VAT' => number_format($row->vat_amount, 2),
                'VATABLE' => number_format($row->vatable_sales, 2),
                'VAT_EXEMPT' => number_format($row->vat_exempt_sales, 2),
                'ZERO_RATED' => number_format($row->zero_rated_sales, 2),
                'PAID' => number_format($row->paid_amount, 2),
                'CHANGE' => number_format($row->change_amount, 2),
                'BALANCE' => number_format($row->balance_due, 2),
                'DATE' => '
                    <div class="d-flex flex-column">
                        <span>'.optional($row->ordered_at ? Carbon::parse($row->ordered_at) : null)?->format('M d, Y').'</span>
                        <small class="text-muted">'.optional($row->ordered_at ? Carbon::parse($row->ordered_at) : null)?->format('h:i A').'</small>
                    </div>
                ',
            ];
        })->values();
    }

    public function generatePaymentSummaryTable(Request $request)
    {
        $filterPaymentMethod = $request->input('filter_payment_method');
        $filterPaymentStatus = $request->input('filter_payment_status');
        $filterCashier = $request->input('filter_cashier');
        $filterDate = $request->input('filter_payment_date');

        $parseRange = function ($range) {
            if (!$range) return null;

            $dates = explode(' - ', $range);

            if (count($dates) !== 2) return null;

            return [
                Carbon::createFromFormat('m/d/Y', trim($dates[0]))->startOfDay(),
                Carbon::createFromFormat('m/d/Y', trim($dates[1]))->endOfDay(),
            ];
        };

        $dateRange = $parseRange($filterDate);

        $payments = DB::table('shop_order_payment')
            ->join('shop_order', 'shop_order.id', '=', 'shop_order_payment.shop_order_id')
            ->when(!empty($filterPaymentMethod), fn($q) =>
                $q->whereIn('shop_order_payment.payment_method_id', (array) $filterPaymentMethod)
            )
            ->when(!empty($filterPaymentStatus), fn($q) =>
                $q->whereIn('shop_order_payment.payment_status', (array) $filterPaymentStatus)
            )
            ->when(!empty($filterCashier), fn($q) =>
                $q->whereIn('shop_order.created_by', (array) $filterCashier)
            )
            ->when($dateRange, fn($q) =>
                $q->whereBetween('shop_order_payment.paid_at', $dateRange)
            )
            ->orderByDesc('shop_order_payment.paid_at')
            ->get();

        return $payments->map(function ($row) {
            $date = Carbon::parse($row->paid_at);

            return [
                'PAYMENT_METHOD' => $row->payment_method_name,
                'ORDER_NO' => $row->order_number,
                'AMOUNT' => number_format($row->payment_amount, 2),
                'STATUS' => '<span class="badge badge-light-primary">'.$row->payment_status.'</span>',
                'CASHIER' => $row->created_by_name ?? 'N/A',
                'REFERENCE' => $row->reference_number ?? '-', 
                'DATE' => '
                    <div class="d-flex flex-column">
                        <span>'.$date->format('M d, Y').'</span>
                        <small class="text-muted">'.$date->format('h:i A').'</small>
                    </div>
                ',
            ];
        });
    }
}