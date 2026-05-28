<?php

namespace App\Http\Controllers;

use TCPDF;
use App\Models\ShopOrder;
use Illuminate\Support\Facades\Auth;

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

        $dummyPdf = new TCPDF(
            'P',
            'mm',
            [80, 2000],
            true,
            'UTF-8',
            false
        );

        $this->configurePdf($dummyPdf);

        $dummyPdf->AddPage();

        $this->renderReceipt(
            $dummyPdf,
            $shopOrder,
            $cashier
        );

        $height =
            $dummyPdf->GetY() + 12;

        /*
        |--------------------------------------------------------------------------
        | FINAL PDF
        |--------------------------------------------------------------------------
        */

        $pdf = new TCPDF(
            'P',
            'mm',
            [80, $height],
            true,
            'UTF-8',
            false
        );

        $this->configurePdf($pdf);

        $pdf->AddPage();

        $this->renderReceipt(
            $pdf,
            $shopOrder,
            $cashier
        );

        return response(
            $pdf->Output(
                'customer-bill.pdf',
                'S'
            ),
            200
        )->header(
            'Content-Type',
            'application/pdf'
        );
    }

    private function configurePdf($pdf): void
    {
        $pdf->setPrintHeader(false);

        $pdf->setPrintFooter(false);

        $pdf->SetMargins(4, 4, 4);

        $pdf->SetAutoPageBreak(false);

        $pdf->SetTextColor(20, 20, 20);

        $pdf->SetDrawColor(180, 180, 180);

        $pdf->SetFont(
            'helvetica',
            '',
            8
        );
    }

    private function renderReceipt(
        TCPDF $pdf,
        ShopOrder $shopOrder,
        $cashier
    ): void {
        // Standard printable width boundary context config for 80mm roll
        $leftMargin = 4;
        $rightMargin = 76;
        $totalWidth = $rightMargin - $leftMargin; // 72mm printable width

        /*
        |--------------------------------------------------------------------------
        | BILL LABEL
        |--------------------------------------------------------------------------
        */
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'CUSTOMER BILL', 0, 1, 'C');
        $pdf->Ln(1);

        /*
        |--------------------------------------------------------------------------
        | ORDER DETAILS
        |--------------------------------------------------------------------------
        */
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

        /*
        |--------------------------------------------------------------------------
        | RECEIPT ITEM TABLE HEADER
        |--------------------------------------------------------------------------
        */
        $pdf->Ln(1);
        $pdf->Cell(0, 3, str_repeat('-', 46), 0, 1, 'C'); // Dynamic text divider string
        
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->Cell(8, 4.5, 'QTY', 0, 0, 'L');
        $pdf->Cell(42, 4.5, 'ITEM DESCRIPTION', 0, 0, 'L');
        $pdf->Cell(22, 4.5, 'AMOUNT', 0, 1, 'R');
        
        $pdf->Cell(0, 3, str_repeat('-', 46), 0, 1, 'C');

        /*
        |--------------------------------------------------------------------------
        | ITEMS MAP & RENDER LOOP
        |--------------------------------------------------------------------------
        */
        $pdf->SetFont('helvetica', '', 7.5);

        foreach ($shopOrder->items as $item) {
            $currentY = $pdf->GetY();
            
            // QTY Column
            $pdf->Cell(8, 4, number_format($item->quantity) . 'x', 0, 0, 'L');

            // MultiCell variable height calculations management for long strings
            $itemName = strtoupper($item->product_name);
            $pdf->MultiCell(42, 4, $itemName, 0, 'L', false, 0);
            $endY = $pdf->GetY();
            
            // Compute calculated multi-line height matching boundary limits
            $lineHeight = $endY - $currentY;
            if ($lineHeight < 4) {
                $lineHeight = 4;
            }

            // Amount Column
            $lineSubtotal = $item->line_subtotal ?? $item->subtotal ?? 0;
            $pdf->SetXY($leftMargin + 50, $currentY);
            $pdf->Cell(22, 4, number_format($lineSubtotal, 2), 0, 1, 'R');

            // Reset tracking coordinates back to baseline after MultiCell line break adjustments
            $pdf->SetY($currentY + $lineHeight);

            /*
            |--------------------------------------------------------------------------
            | REMARKS / NOTES VARIANT MODIFIER BLOCK
            |--------------------------------------------------------------------------
            */
            if (!empty($item->remarks)) {
                $pdf->SetFont('helvetica', 'I', 7);
                $pdf->Cell(8, 3.5, '', 0, 0);
                $pdf->MultiCell(64, 3.5, '* Note: ' . $item->remarks, 0, 'L', false, 1);
                $pdf->SetFont('helvetica', '', 7.5); // Fall back to table defaults
            }
            $pdf->Ln(0.5);
        }

        /*
        |--------------------------------------------------------------------------
        | BILL TOTALS SECTIONS
        |--------------------------------------------------------------------------
        */
        $pdf->Cell(0, 3, str_repeat('-', 46), 0, 1, 'C');
        $pdf->Ln(1);

        $this->renderAmountLine($pdf, 'Subtotal', $shopOrder->subtotal);

        // Applied Service Charges/Surcharges Map
        if (isset($shopOrder->appliedCharges)) {
            foreach ($shopOrder->appliedCharges as $charge) {
                $this->renderAmountLine($pdf, $charge->charge_type_name, $charge->charge_amount);
            }
        }

        // Applied Discount Deductions Map
        if (isset($shopOrder->appliedDiscounts)) {
            foreach ($shopOrder->appliedDiscounts as $discount) {
                $this->renderAmountLine($pdf, $discount->discount_type_name, -$discount->discount_amount);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | GRAND TOTAL WRAPPER BOX
        |--------------------------------------------------------------------------
        */
        $pdf->Ln(1.5);
        $pdf->Cell(0, 2, str_repeat('=', 31), 0, 1, 'R'); // Clean dual line separation anchor

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(35, 6, 'TOTAL DUE', 0, 0, 'L');
        $pdf->Cell(37, 6, 'PHP ' . number_format($shopOrder->net_total, 2), 0, 1, 'R');
        
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->Cell(0, 2, str_repeat('=', 31), 0, 1, 'R');
        $pdf->Ln(2);

        /*
        |--------------------------------------------------------------------------
        | TAX ACCOUNTING BREAKDOWN
        |--------------------------------------------------------------------------
        */
        $pdf->SetFont('helvetica', '', 6.8);
        $this->renderAmountLine($pdf, 'VATable Sales', $shopOrder->vatable_sales);
        $this->renderAmountLine($pdf, 'VAT Exempt Sales', $shopOrder->vat_exempt_sales);
        $this->renderAmountLine($pdf, 'VAT Amount (12%)', $shopOrder->vat_amount);

        /*
        |--------------------------------------------------------------------------
        | TERMINAL FOOTER DECLARES
        |--------------------------------------------------------------------------
        */
        $pdf->Ln(4);
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->MultiCell(0, 4, 'Thank you for dining with us!', 0, 'C');
        $pdf->Ln(1);

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->MultiCell(0, 4, 'THIS IS NOT AN OFFICIAL RECEIPT', 0, 'C');

        /*
        |--------------------------------------------------------------------------
        | SYSTEM TRACE FOOTPRINT
        |--------------------------------------------------------------------------
        */
        $pdf->Ln(2);
        $pdf->SetFont('helvetica', '', 6);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->MultiCell(0, 3, 'System Generated: ' . now()->format('M d, Y h:i:s A'), 0, 'C');
    }

    private function renderAmountLine(TCPDF $pdf, string $label, float $amount): void
    {
        // Total design width matches exact item column structure layout bounds cleanly
        $pdf->Cell(45, 4, $label, 0, 0, 'L');
        $pdf->Cell(27, 4, number_format($amount, 2), 0, 1, 'R');
    }
}