<?php

namespace App\Http\Controllers;

use TCPDF;
use App\Models\ShopOrder;
use App\Models\ShopRegisterSession;
use App\Models\ShopRegister;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShopOrderPrintController extends Controller
{
    public function printBill(ShopOrder $shopOrder)
    {
        /*
        |--------------------------------------------------------------------------
        | LOAD RELATIONSHIPS
        |--------------------------------------------------------------------------
        */
        $shopOrder->load([
            'items',
            'appliedCharges',
            'appliedDiscounts',
            'shopRegister',
            'floorPlan',
            'floorPlanTable',
        ]);

        /*
        |--------------------------------------------------------------------------
        | CASHIER
        |--------------------------------------------------------------------------
        */
        $cashier = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | PDF HEIGHT CALCULATION
        |--------------------------------------------------------------------------
        */
        $dummyPdf = new TCPDF('P', 'mm', [80, 2000], true, 'UTF-8', false);
        $this->configurePdf($dummyPdf);
        $dummyPdf->AddPage();
        $this->renderReceipt($dummyPdf, $shopOrder, $cashier);
        $height = $dummyPdf->GetY() + 12;

        /*
        |--------------------------------------------------------------------------
        | FINAL PDF
        |--------------------------------------------------------------------------
        */
        $pdf = new TCPDF('P', 'mm', [80, $height], true, 'UTF-8', false);
        $this->configurePdf($pdf);
        $pdf->AddPage();
        $this->renderReceipt($pdf, $shopOrder, $cashier);

        return response($pdf->Output('customer-bill.pdf', 'S'), 200)
            ->header('Content-Type', 'application/pdf');
    }

    public function printReceipt(ShopOrder $shopOrder)
    {
        /*
        |--------------------------------------------------------------------------
        | LOAD RELATIONSHIPS
        |--------------------------------------------------------------------------
        */
        $shopOrder->load([
            'items',
            'appliedCharges',
            'appliedDiscounts',
            'shopRegister',
            'floorPlan',
            'floorPlanTable',
        ]);

        /*
        |--------------------------------------------------------------------------
        | CASHIER
        |--------------------------------------------------------------------------
        */
        $cashier = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | PDF HEIGHT CALCULATION
        |--------------------------------------------------------------------------
        */
        $dummyPdf = new TCPDF('P', 'mm', [80, 2000], true, 'UTF-8', false);
        $this->configurePdf($dummyPdf);
        $dummyPdf->AddPage();
        $this->renderReceipt($dummyPdf, $shopOrder, $cashier, 'No');
        $height = $dummyPdf->GetY() + 12;

        /*
        |--------------------------------------------------------------------------
        | FINAL PDF
        |--------------------------------------------------------------------------
        */
        $pdf = new TCPDF('P', 'mm', [80, $height], true, 'UTF-8', false);
        $this->configurePdf($pdf);
        $pdf->AddPage();
        $this->renderReceipt($pdf, $shopOrder, $cashier, 'No');

        return response($pdf->Output('customer-receipt.pdf', 'S'), 200)
            ->header('Content-Type', 'application/pdf');
    }

    public function printKitchenTicket(int $ticketId)
    {
        $kitchenTicket = DB::table('kitchen_ticket')
            ->where('id', $ticketId)
            ->first();

        if (!$kitchenTicket) {
            abort(404, 'Kitchen ticket not found.');
        }

        $shopOrder = ShopOrder::with([
            'items' => function ($query) {
                $query->where('quantity', '>', 0);
            },
            'floorPlan',
            'floorPlanTable',
        ])->find($kitchenTicket->shop_order_id);

        if (!$shopOrder) {
            abort(404, 'Shop order not found for this ticket.');
        }

        // 80mm x 50mm Terminal Ticket Size
        $pdf = new TCPDF('P', 'mm', [80, 50], true, 'UTF-8', false);
        
        $pdf->SetMargins(2, 2, 2); // Tight 2mm margins
        $pdf->SetAutoPageBreak(false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // --- Inject JavaScript Auto-Print Command ---
        // print(true) opens the print dialog automatically.
        // print(false) attempts silent printing (if supported by POS environment).
        $pdf->IncludeJS("print(true);");

        $pdf->AddPage();
        $this->renderKitchenTicket($pdf, $shopOrder);

        DB::table('kitchen_ticket')
            ->where('id', $ticketId)
            ->update([
                'print_count' => DB::raw('print_count + 1'),
                'last_printed_at' => now(),
            ]);

        return response($pdf->Output("kitchen-ticket-{$shopOrder->order_number}.pdf", 'I'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="kitchen-ticket-' . $shopOrder->order_number . '.pdf"');
    }

    private function renderKitchenTicket(TCPDF $pdf, ShopOrder $shopOrder): void
    {
        // --- 1. HEADER METADATA ---
        $pdf->SetFont('helvetica', 'B', 12);
        $floorPlanName = $shopOrder->floor_plan_name ?? '';
        $locationOrId = $shopOrder->table_number 
            ? 'TBL ' . $shopOrder->table_number 
            : '#' . $shopOrder->order_number;
        $pdf->Cell(0, 5, trim($floorPlanName . ' ' . $locationOrId), 0, 1, 'C');

        // Ticket Number & Timestamp Line
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(38, 3.5, 'TICKET #' . $shopOrder->order_number, 0, 0, 'L');
        
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(38, 3.5, now()->format('h:i A | M d'), 0, 1, 'R');

        // Double Divider Line
        $pdf->SetFont('helvetica', 'B', 6);
        $pdf->Cell(0, 1, str_repeat('=', 52), 0, 1, 'C');

        // --- 2. BUILD HTML TABLE FOR ITEMS ---
        $html = '
        <style>
            table {
                width: 100%;
                font-family: helvetica;
            }
            th {
                font-size: 7pt;
                font-weight: bold;
                border-bottom: 0.5pt solid #000;
            }
            td.qty {
                width: 15%;
                font-size: 9pt;
                font-weight: bold;
                vertical-align: top;
            }
            td.item {
                width: 85%;
                font-size: 8pt;
                font-weight: bold;
                vertical-align: top;
            }
            .note {
                font-size: 7pt;
                font-weight: bold;
                color: #222222;
                padding-top: 1px;
            }
        </style>
        <table cellpadding="1" cellspacing="0">
            <thead>
                <tr>
                    <th style="width: 15%;">QTY</th>
                    <th style="width: 85%;">ITEM DETAILS</th>
                </tr>
            </thead>
            <tbody>';

        $hasItems = false;

        foreach ($shopOrder->items as $item) {
            if ($item->status === 'Cancelled' || (float) $item->quantity <= 0) {
                continue;
            }

            $hasItems = true;
            $qty = number_format((float) $item->quantity) . 'x';
            $itemName = htmlspecialchars(strtoupper($item->product_name));

            $html .= '<tr>';
            $html .= '<td class="qty">' . $qty . '</td>';
            $html .= '<td class="item">' . $itemName;

            if (!empty($item->order_note)) {
                $note = htmlspecialchars(strtoupper($item->order_note));
                $html .= '<div class="note">&gt;&gt; NOTE: ' . $note . '</div>';
            }

            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        if ($hasItems) {
            // Output the HTML Table block (width 76mm fits within 80mm paper with 2mm margins)
            $pdf->writeHTMLCell(45, 0, $pdf->GetX(), $pdf->GetY(), $html, 0, 1, false, true, 'L', true);
        }

        // --- 3. BOTTOM TERMINAL FOOTER ---
        if ($pdf->GetY() <= 45) {
            $pdf->Cell(0, 1, str_repeat('=', 52), 0, 1, 'C');
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->Cell(0, 2.5, '*** END OF TICKET ***', 0, 1, 'C');
        } else {
            $pdf->SetFont('helvetica', 'B', 6);
            $pdf->Cell(0, 2, '*** MORE ITEMS ON NEXT TICKET ***', 0, 1, 'C');
        }
    }

    /**
     * 1. REPORT: List of all shop orders per shop register session
     */
    public function printRegisterOrdersReport(ShopRegister $shopRegister)
    {
        $cashier = Auth::user();

        $session = ShopRegisterSession::query()
            ->where('shop_register_id', $shopRegister->id)
            ->whereNull('close_time')
            ->latest('id')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No active open session found for this register.',
            ], 422);
        }

        // Deep-load relationships matching structural itemized loops
        $session->load([
            'shopRegister'
        ]);

        // 4000mm ceiling padding window to absorb multi-tiered multicell loops safely
        $dummyPdf = new TCPDF('P', 'mm', [80, 8000], true, 'UTF-8', false);
        $this->configurePdf($dummyPdf);
        $dummyPdf->AddPage();
        $this->renderRegisterOrders($dummyPdf, $session, $cashier);
        $height = $dummyPdf->GetY() + 12;

        $pdf = new TCPDF('P', 'mm', [80, $height], true, 'UTF-8', false);
        $this->configurePdf($pdf);
        $pdf->AddPage();
        $this->renderRegisterOrders($pdf, $session, $cashier);

        return response($pdf->Output("orders-itemized-report-register-{$shopRegister->id}.pdf", 'S'), 200)
            ->header('Content-Type', 'application/pdf');
    }

    /**
     * 2. REPORT: Shop order payments summary breakdown per register session
     */
    public function printRegisterPaymentsReport(ShopRegister $shopRegister)
    {
        $cashier = Auth::user();

        $session = ShopRegisterSession::query()
            ->where('shop_register_id', $shopRegister->id)
            ->whereNull('close_time')
            ->latest('id')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No active open session found for this register.',
            ], 422);
        }

        $dummyPdf = new TCPDF('P', 'mm', [80, 6000], true, 'UTF-8', false);
        $this->configurePdf($dummyPdf);
        $dummyPdf->AddPage();
        $this->renderRegisterPayments($dummyPdf, $session, $cashier);
        $height = $dummyPdf->GetY() + 12;

        $pdf = new TCPDF('P', 'mm', [80, $height], true, 'UTF-8', false);
        $this->configurePdf($pdf);
        $pdf->AddPage();
        $this->renderRegisterPayments($pdf, $session, $cashier);

        return response($pdf->Output("payments-itemized-report-register-{$shopRegister->id}.pdf", 'S'), 200)
            ->header('Content-Type', 'application/pdf');
    }

    private function configurePdf($pdf): void
    {
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(4, 4, 4);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetFont('helvetica', '', 8);
    }

    private function renderReceipt(TCPDF $pdf, ShopOrder $shopOrder, $cashier, $withheader = 'Yes'): void 
    {
        $leftMargin = 4;
        $rightMargin = 76;
        $totalWidth = $rightMargin - $leftMargin;

        if($withheader == 'Yes'){
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(0, 5, 'CUSTOMER BILL', 0, 1, 'C');
            $pdf->Ln(1);
        }        

        $pdf->SetFont('helvetica', '', 7.5);
        $details = [
            'Order No' => $shopOrder->order_number,
            'Date'     => now()->format('M d, Y h:i A'),
            'Cashier'  => $cashier?->name ?? 'N/A',
            'Register' => $shopOrder?->shopRegister?->shop_register_name ?? 'Main Reg',
        ];

        if ($shopOrder?->floorPlan?->floor_plan_name) {
            $details['Area'] = $shopOrder->floorPlan->floor_plan_name;
        }
        if ($shopOrder->table_number) {
            $details['Table'] = $shopOrder->table_number;
        }

        foreach ($details as $label => $value) {
            $pdf->Cell(16, 4, $label, 0, 0, 'L');
            $pdf->Cell(3, 4, ':', 0, 0, 'C');
            $pdf->Cell(0, 4, $value, 0, 1, 'L');
        }

        $pdf->Ln(1);
        $pdf->Cell(0, 3, str_repeat('-', 46), 0, 1, 'C'); 
        
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->Cell(8, 4.5, 'QTY', 0, 0, 'L');
        $pdf->Cell(42, 4.5, 'ITEM DESCRIPTION', 0, 0, 'L');
        $pdf->Cell(22, 4.5, 'AMOUNT', 0, 1, 'R');
        
        $pdf->Cell(0, 3, str_repeat('-', 46), 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 7.5);
        foreach ($shopOrder->items as $item) {
            // Skip items with 0, null, or negative quantities
            if (empty($item->quantity) || $item->quantity <= 0) {
                continue;
            }

            $currentY = $pdf->GetY();
            $itemName = strtoupper($item->product_name);
            
            // 1. Calculate the real height required for the item name MultiCell
            $cellWidth = 42;
            $cellHeight = 4; // minimum row height
            $calculatedHeight = $pdf->GetStringHeight($cellWidth, $itemName);
            $itemHeight = max($cellHeight, $calculatedHeight);

            // 2. Render QTY and Amount aligned with the top of the item name
            $pdf->Cell(8, $itemHeight, number_format($item->quantity) . 'x', 0, 0, 'L');
            
            // Render item name (using 1 for $ln to push cursor down naturally)
            $pdf->MultiCell($cellWidth, $cellHeight, $itemName, 0, 'L', false, 0);
            
            // Move to the exact position for Amount cell
            $lineSubtotal = $item->line_subtotal ?? $item->subtotal ?? 0;
            $pdf->SetXY($leftMargin + 50, $currentY);
            $pdf->Cell(22, $itemHeight, number_format($lineSubtotal, 2), 0, 1, 'R');
            
            // 3. Explicitly reset Y position below the longest point of the item row
            $pdf->SetY($currentY + $itemHeight);

            // 4. Render Notes without breaking layout
            if (!empty($item->order_note)) {
                $pdf->SetFont('helvetica', 'I', 7);
                
                // Re-calculate note height to prevent it from clipping into the next item
                $noteText = '* Note: ' . $item->order_note;
                $noteHeight = max(3.5, $pdf->GetStringHeight(64, $noteText));
                
                $pdf->Cell(8, $noteHeight, '', 0, 0);
                $pdf->MultiCell(64, 3.5, $noteText, 0, 'L', false, 1);
                
                $pdf->SetFont('helvetica', '', 7.5);
            }
            
            $pdf->Ln(1); // Small space before the next item loop begins
        }

        $pdf->Cell(0, 3, str_repeat('-', 46), 0, 1, 'C');
        $pdf->Ln(1);

        $this->renderAmountLine($pdf, 'Subtotal', $shopOrder->subtotal);

        if (isset($shopOrder->appliedCharges)) {
            foreach ($shopOrder->appliedCharges as $charge) {
                $this->renderAmountLine($pdf, $charge->charge_type_name, $charge->charge_amount);
            }
        }
        if (isset($shopOrder->appliedDiscounts)) {
            foreach ($shopOrder->appliedDiscounts as $discount) {
                $this->renderAmountLine($pdf, $discount->discount_type_name, -$discount->discount_amount);
            }
        }

        $pdf->Ln(1.5);
        $pdf->Cell(0, 2, str_repeat('=', 31), 0, 1, 'R');

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(35, 6, 'TOTAL DUE', 0, 0, 'L');
        $pdf->Cell(37, 6, 'PHP ' . number_format($shopOrder->net_total, 2), 0, 1, 'R');
        
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->Cell(0, 2, str_repeat('=', 31), 0, 1, 'R');
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', '', 6.8);
        $this->renderAmountLine($pdf, 'VATable Sales', $shopOrder->vatable_sales);
        $this->renderAmountLine($pdf, 'VAT Exempt Sales', $shopOrder->vat_exempt_sales);
        $this->renderAmountLine($pdf, 'VAT Amount (12%)', $shopOrder->vat_amount);

        $pdf->Ln(4);
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->MultiCell(0, 4, 'Thank you for dining with us!', 0, 'C');
        $pdf->Ln(1);

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->MultiCell(0, 4, 'THIS IS NOT AN OFFICIAL RECEIPT', 0, 'C');

        $pdf->Ln(2);
        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->MultiCell(0, 3, 'System Generated: ' . now()->format('M d, Y h:i:s A'), 0, 'C');
    }

    /**
     * Report 1 Structural Logic
     */
    private function renderRegisterOrders(TCPDF $pdf, ShopRegisterSession $session, $cashier): void
    {
        $leftMargin = 4;

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'ORDER SUMMARY', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->Cell(0, 4, 'ITEMIZED END-OF-DAY REPORT', 0, 1, 'C');
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', '', 7.5);
        $this->renderMetaLine($pdf, 'Register', $session->shop_register_name);
        $this->renderMetaLine($pdf, 'Session ID', '#' . $session->id);
        $this->renderMetaLine($pdf, 'Opened By', $session->open_user_name ?? 'System');
        $this->renderMetaLine($pdf, 'Open Time', date('M d, Y h:i A', strtotime($session->open_time)));
        $this->renderMetaLine($pdf, 'Print Date', now()->format('M d, Y h:i A'));

        // Query all orders with explicit nested items eager loaded to mitigate N+1 loops
        $orders = ShopOrder::with(['items'])
            ->where('shop_register_session_id', $session->id)
            ->orderBy('id', 'asc')
            ->get();

        // Structural Financial Context Metric Targets
        $overallSubtotal = 0;
        $overallDiscounts = 0;
        $overallCharges = 0;
        $overallNetTotal = 0;
        $overallVatAmount = 0;

        foreach ($orders as $order) {
            $pdf->Ln(2);
            $pdf->Cell(0, 2, str_repeat('=', 46), 0, 1, 'C');
            
            // Order Header block metadata
            $pdf->SetFont('helvetica', 'B', 7.5);
            $pdf->Cell(35, 4, 'ORDER: ' . $order->order_number, 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 7);
            $pdf->Cell(37, 4, '[' . strtoupper($order->order_status) . '] • ' . $order->order_type, 0, 1, 'R');
            
            if ($order->customer_name) {
                $pdf->Cell(0, 3.5, 'Customer: ' . $order->customer_name, 0, 1, 'L');
            }
            
            $pdf->Cell(0, 1, str_repeat('-', 46), 0, 1, 'C');

            // Render Child Line Items matching explicit layout constraints
            $pdf->SetFont('helvetica', '', 7.5);
            foreach ($order->items as $item) {
                $currentY = $pdf->GetY();
                $pdf->Cell(8, 4, number_format($item->quantity) . 'x', 0, 0, 'L');

                $itemName = strtoupper($item->product_name);
                $pdf->MultiCell(42, 4, $itemName, 0, 'L', false, 0);
                $endY = $pdf->GetY();

                $lineHeight = $endY - $currentY;
                if ($lineHeight < 4) {
                    $lineHeight = 4;
                }

                $pdf->SetXY($leftMargin + 50, $currentY);
                $pdf->Cell(22, 4, number_format($item->line_subtotal, 2), 0, 1, 'R');
                $pdf->SetY($currentY + $lineHeight);

                if (!empty($item->order_note)) {
                    $pdf->SetFont('helvetica', 'I', 7);
                    $pdf->Cell(8, 3.5, '', 0, 0);
                    $pdf->MultiCell(64, 3.5, '* Note: ' . $item->order_note, 0, 'L', false, 1);
                    $pdf->SetFont('helvetica', '', 7.5);
                }
            }

            // Individual Ticket Totals Footer
            $pdf->Cell(0, 1, str_repeat('.', 46), 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 7);
            
            // Exclude non-revenue lines (Cancelled/Voided) from running financial metrics arrays
            if (!in_array($order->order_status, ['Cancelled', 'Voided'])) {
                $overallSubtotal += $order->subtotal;
                $overallDiscounts += $order->discount_total;
                $overallCharges += $order->charge_total;
                $overallNetTotal += $order->net_total;
                $overallVatAmount += $order->vat_amount;
                
                $this->renderAmountLine($pdf, 'Ticket Subtotal', $order->subtotal);
                if ($order->discount_total > 0) {
                    $this->renderAmountLine($pdf, 'Ticket Discounts', -$order->discount_total);
                }
                if ($order->charge_total > 0) {
                    $this->renderAmountLine($pdf, 'Ticket Charges', $order->charge_total);
                }
                $pdf->SetFont('helvetica', 'B', 7);
                $this->renderAmountLine($pdf, 'Ticket Net Paid', $order->net_total);
            } else {
                $pdf->SetFont('helvetica', 'I', 7);
                $pdf->Cell(0, 4, 'Transaction Non-Revenue Row (Voided/Cancelled)', 0, 1, 'L');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | OVERALL TOTALS GRAND SUMMARY SECTION
        |--------------------------------------------------------------------------
        */
        $pdf->Ln(4);
        $pdf->Cell(0, 3, str_repeat('=', 31), 0, 1, 'R');
        $pdf->SetFont('helvetica', 'B', 8.5);
        $pdf->Cell(0, 5, 'GRAND OVERALL SUMMARY', 0, 1, 'L');
        $pdf->Cell(0, 1, str_repeat('-', 46), 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 7.5);
        $this->renderAmountLine($pdf, 'Gross Subtotal', $overallSubtotal);
        $this->renderAmountLine($pdf, 'Total Deductions (Discounts)', -$overallDiscounts);
        $this->renderAmountLine($pdf, 'Total Applied Charges', $overallCharges);
        $this->renderAmountLine($pdf, 'Total Accumulative VAT (12%)', $overallVatAmount);
        
        $pdf->Ln(1);
        $pdf->SetFont('helvetica', 'B', 9.5);
        $pdf->Cell(35, 6, 'OVERALL TOTAL DUE', 0, 0, 'L');
        $pdf->Cell(37, 6, 'PHP ' . number_format($overallNetTotal, 2), 0, 1, 'R');
        
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->Cell(0, 2, str_repeat('=', 31), 0, 1, 'R');
    }

    /**
     * Report 2 Structural Logic
     */
    private function renderRegisterPayments(TCPDF $pdf, ShopRegisterSession $session, $cashier): void
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'PAYMENT SUMMARY', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->Cell(0, 4, 'ORDER GROUPED LEDGER REPORT', 0, 1, 'C');
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', '', 7.5);
        $this->renderMetaLine($pdf, 'Register', $session->shop_register_name);
        $this->renderMetaLine($pdf, 'Session ID', '#' . $session->id);
        $this->renderMetaLine($pdf, 'Opened By', $session->open_user_name ?? 'System');
        $this->renderMetaLine($pdf, 'Open Time', date('M d, Y h:i A', strtotime($session->open_time)));
        $this->renderMetaLine($pdf, 'Print Date', now()->format('M d, Y h:i A'));

        // Query orders with active payment lines
        $orders = ShopOrder::with(['payments' => function ($q) {
                $q->orderBy('id', 'asc');
            }])
            ->where('shop_register_session_id', $session->id)
            ->orderBy('id', 'asc')
            ->get();

        $pdf->Cell(0, 3, str_repeat('-', 46), 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(25, 4, 'ORDER', 0, 0, 'L');
        $pdf->Cell(25, 4, 'REF NUMBER', 0, 0, 'L');
        $pdf->Cell(22, 4, 'AMOUNT', 0, 1, 'R');
        $pdf->Cell(0, 3, str_repeat('-', 46), 0, 1, 'C');

        $grandPaymentsTotal = 0;
        $methodSummaries = [];

        foreach ($orders as $order) {
            if ($order->payments->isEmpty()) {
                continue;
            }

            $pdf->SetFont('helvetica', 'B', 7.5);
            $pdf->Cell(0, 4.5, "ORDER: " . $order->order_number . " (" . $order->payment_status . ")", 0, 1, 'L');

            $pdf->SetFont('helvetica', '', 7);
            foreach ($order->payments as $payment) {
                // Skip tracking lines flagged with transaction errors
                if (in_array($payment->payment_status, ['Failed', 'Voided'])) {
                    continue;
                }

                $amt = $payment->payment_amount;
                
                // Track refund tracking logic parameters 
                if ($payment->payment_status === 'Refunded') {
                    $amt = -$amt;
                }

                $grandPaymentsTotal += $amt;

                // Build a structured map for the final overall summary block
                $methodName = $payment->payment_method_name;
                if (!isset($methodSummaries[$methodName])) {
                    $methodSummaries[$methodName] = 0;
                }
                $methodSummaries[$methodName] += $amt;

                // Render detail row metrics
                $pdf->Cell(4, 4, '', 0, 0); // Visual indent spacing anchor
                $pdf->Cell(28, 4, strtoupper($methodName), 0, 0, 'L');
                
                $refNo = !empty($payment->reference_number) ? $payment->reference_number : '-';
                $pdf->Cell(18, 4, $refNo, 0, 0, 'L');
                
                $pdf->Cell(22, 4, number_format($amt, 2), 0, 1, 'R');
            }
            $pdf->Cell(0, 1, str_repeat('.', 46), 0, 1, 'C');
        }

        /*
        |--------------------------------------------------------------------------
        | METHOD SUMMARY & BALANCING AUDIT SECTION
        |--------------------------------------------------------------------------
        */
        $pdf->Ln(3);
        $pdf->Cell(0, 3, str_repeat('=', 31), 0, 1, 'R');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(0, 4.5, 'TOTALS BY PAYMENT METHOD:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 7.5);

        foreach ($methodSummaries as $method => $sum) {
            $this->renderAmountLine($pdf, strtoupper($method), $sum);
        }

        $pdf->Cell(0, 2, str_repeat('-', 46), 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 7.5);
        $this->renderAmountLine($pdf, 'OPENING CASH DRAWER', $session->open_amount);
        $this->renderAmountLine($pdf, 'TOTAL NET OVERALL PAYMENTS', $grandPaymentsTotal);
        
        $expectedDrawer = $session->open_amount + $grandPaymentsTotal;
        $pdf->Ln(1);
        
        $pdf->SetFont('helvetica', 'B', 9);
        $this->renderAmountLine($pdf, 'EXPECTED DRAWER TOTAL', $expectedDrawer);
        $pdf->Cell(0, 2, str_repeat('=', 31), 0, 1, 'R');
    }

    private function renderAmountLine(TCPDF $pdf, string $label, float $amount): void
    {
        $pdf->Cell(45, 4, $label, 0, 0, 'L');
        $pdf->Cell(27, 4, number_format($amount, 2), 0, 1, 'R');
    }

    private function renderMetaLine(TCPDF $pdf, string $label, string $value): void
    {
        $pdf->Cell(16, 3.8, $label, 0, 0, 'L');
        $pdf->Cell(3, 3.8, ':', 0, 0, 'C');
        $pdf->Cell(0, 3.8, $value, 0, 1, 'L');
    }
}