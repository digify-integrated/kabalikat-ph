<?php

namespace App\Http\Controllers;

use TCPDF;
use App\Models\Company;
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

        /*
        |--------------------------------------------------------------------------
        | BRAND HEADER
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | COMPANY NAME
        |--------------------------------------------------------------------------
        */

        $pdf->SetFont(
            'helvetica',
            'B',
            11
        );

        /*
        |--------------------------------------------------------------------------
        | COMPANY DETAILS
        |--------------------------------------------------------------------------
        */

        $pdf->SetFont(
            'helvetica',
            '',
            7
        );

        /*
        |--------------------------------------------------------------------------
        | BILL LABEL
        |--------------------------------------------------------------------------
        */

        $pdf->Ln(2);

        $pdf->SetFillColor(
            235,
            235,
            235
        );

        $pdf->SetFont(
            'helvetica',
            'B',
            9
        );

        $pdf->Cell(
            0,
            6,
            'CUSTOMER BILL',
            0,
            1,
            'C',
            true
        );

        /*
        |--------------------------------------------------------------------------
        | ORDER DETAILS
        |--------------------------------------------------------------------------
        */

        $pdf->Ln(2);

        $pdf->SetFont(
            'helvetica',
            '',
            7.5
        );

        $details = [

            'Order No' =>
                $shopOrder->order_number,

            'Date' =>
                now()->format(
                    'M d, Y h:i A'
                ),

            'Cashier' =>
                $cashier?->name ?? '',

            'Register' =>
                $shopOrder?->shopRegister?->shop_register_name,
        ];

        if ($shopOrder?->floorPlan?->floor_plan_name) {

            $details['Area'] =
                $shopOrder
                    ->floorPlan
                    ->floor_plan_name;
        }

        if ($shopOrder->table_number) {

            $details['Table'] =
                $shopOrder->table_number;
        }

        foreach ($details as $label => $value) {

            $pdf->Cell(
                19,
                4,
                $label,
                0,
                0
            );

            $pdf->Cell(
                2,
                4,
                ':',
                0,
                0
            );

            $pdf->Cell(
                0,
                4,
                $value,
                0,
                1
            );
        }

        /*
        |--------------------------------------------------------------------------
        | DIVIDER
        |--------------------------------------------------------------------------
        */

        $pdf->Ln(1);

        $pdf->Line(
            4,
            $pdf->GetY(),
            76,
            $pdf->GetY()
        );

        $pdf->Ln(1.5);

        /*
        |--------------------------------------------------------------------------
        | ITEM TABLE HEADER
        |--------------------------------------------------------------------------
        */

        $pdf->SetFont(
            'helvetica',
            'B',
            7.5
        );

        $pdf->Cell(
            10,
            5,
            'QTY',
            0,
            0,
            'L'
        );

        $pdf->Cell(
            38,
            5,
            'ITEM',
            0,
            0,
            'L'
        );

        $pdf->Cell(
            24,
            5,
            'AMOUNT',
            0,
            1,
            'R'
        );

        $pdf->Line(
            4,
            $pdf->GetY(),
            76,
            $pdf->GetY()
        );

        /*
        |--------------------------------------------------------------------------
        | ITEMS
        |--------------------------------------------------------------------------
        */

        $pdf->SetFont(
            'helvetica',
            '',
            7.5
        );

        foreach (
            $shopOrder->items
            as $item
        ) {

            $startY = $pdf->GetY();

            $pdf->Cell(
                10,
                5,
                number_format(
                    $item->quantity
                ),
                0,
                0,
                'L'
            );

            $pdf->MultiCell(
                38,
                5,
                strtoupper(
                    $item->product_name
                ),
                0,
                'L',
                false,
                0
            );

            $lineSubtotal =
                $item->line_subtotal
                ?? $item->subtotal
                ?? 0;

            $pdf->Cell(
                24,
                5,
                'P ' .
                number_format(
                    $lineSubtotal,
                    2
                ),
                0,
                1,
                'R'
            );

            /*
            |--------------------------------------------------------------------------
            | NOTES / VARIANTS
            |--------------------------------------------------------------------------
            */

            if (!empty($item->remarks)) {

                $pdf->SetFont(
                    'helvetica',
                    'I',
                    6.5
                );

                $pdf->Cell(
                    10,
                    3,
                    '',
                    0,
                    0
                );

                $pdf->MultiCell(
                    62,
                    3,
                    'Note: ' .
                    $item->remarks,
                    0,
                    'L',
                    false,
                    1
                );

                $pdf->SetFont(
                    'helvetica',
                    '',
                    7.5
                );
            }

            if (
                $pdf->GetY() - $startY < 5
            ) {

                $pdf->Ln(0.5);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DIVIDER
        |--------------------------------------------------------------------------
        */

        $pdf->Ln(1);

        $pdf->Line(
            4,
            $pdf->GetY(),
            76,
            $pdf->GetY()
        );

        $pdf->Ln(2);

        /*
        |--------------------------------------------------------------------------
        | TOTALS
        |--------------------------------------------------------------------------
        */

        $pdf->SetFont(
            'helvetica',
            '',
            7.5
        );

        $this->renderAmountLine(
            $pdf,
            'Subtotal',
            $shopOrder->subtotal
        );

        /*
        |--------------------------------------------------------------------------
        | CHARGES
        |--------------------------------------------------------------------------
        */

        foreach (
            $shopOrder->appliedCharges
            as $charge
        ) {

            $this->renderAmountLine(
                $pdf,
                $charge->charge_type_name,
                $charge->charge_amount
            );
        }

        /*
        |--------------------------------------------------------------------------
        | DISCOUNTS
        |--------------------------------------------------------------------------
        */

        foreach (
            $shopOrder->appliedDiscounts
            as $discount
        ) {

            $this->renderAmountLine(
                $pdf,
                $discount->discount_type_name,
                $discount->discount_amount
            );
        }

        /*
        |--------------------------------------------------------------------------
        | GRAND TOTAL BOX
        |--------------------------------------------------------------------------
        */

        $pdf->Ln(2);

        $pdf->SetFillColor(
            245,
            245,
            245
        );

        $pdf->SetFont(
            'helvetica',
            'B',
            10
        );

        $pdf->Cell(
            35,
            8,
            'TOTAL DUE',
            0,
            0,
            'L',
            true
        );

        $pdf->Cell(
            37,
            8,
            'P ' .
            number_format(
                $shopOrder->net_total,
                2
            ),
            0,
            1,
            'R',
            true
        );

        /*
        |--------------------------------------------------------------------------
        | VAT BREAKDOWN
        |--------------------------------------------------------------------------
        */

        $pdf->Ln(2);

        $pdf->SetFont(
            'helvetica',
            '',
            6.8
        );

        $this->renderAmountLine(
            $pdf,
            'VATable Sales',
            $shopOrder->vatable_sales
        );

        $this->renderAmountLine(
            $pdf,
            'VAT Exempt Sales',
            $shopOrder->vat_exempt_sales
        );

        $this->renderAmountLine(
            $pdf,
            'VAT Amount',
            $shopOrder->vat_amount
        );

        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        $pdf->Ln(4);

        $pdf->SetFont(
            'helvetica',
            '',
            7
        );

        $pdf->MultiCell(
            0,
            3.5,
            'Thank you for dining with us!',
            0,
            'C'
        );

        $pdf->Ln(1);

        $pdf->SetFont(
            'helvetica',
            'B',
            6.8
        );

        $pdf->MultiCell(
            0,
            3.5,
            'THIS IS NOT AN OFFICIAL RECEIPT',
            0,
            'C'
        );

        /*
        |--------------------------------------------------------------------------
        | POWERED BY
        |--------------------------------------------------------------------------
        */

        $pdf->Ln(2);

        $pdf->SetFont(
            'helvetica',
            '',
            6
        );

        $pdf->SetTextColor(
            120,
            120,
            120
        );

        $pdf->MultiCell(
            0,
            3,
            'Generated ' .
            now()->format(
                'M d, Y h:i:s A'
            ),
            0,
            'C'
        );
    }

    private function renderAmountLine(
        TCPDF $pdf,
        string $label,
        float $amount
    ): void {

        $pdf->Cell(
            42,
            4,
            $label,
            0,
            0
        );

        $pdf->Cell(
            30,
            4,
            'P ' .
            number_format(
                $amount,
                2
            ),
            0,
            1,
            'R'
        );
    }
}