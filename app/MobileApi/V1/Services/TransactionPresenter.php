<?php

namespace App\MobileApi\V1\Services;

use App\Transaction;
use Carbon\Carbon;

class TransactionPresenter
{
    public function summary(Transaction $transaction): array
    {
        return [
            'id' => (int) $transaction->id,
            'type' => $transaction->type,
            'sub_type' => $transaction->sub_type,
            'reference' => $transaction->invoice_no ?: $transaction->ref_no,
            'invoice_no' => $transaction->invoice_no,
            'ref_no' => $transaction->ref_no,
            'status' => $transaction->status,
            'payment_status' => $transaction->payment_status,
            'amount' => round((float) $transaction->final_total, 4),
            'tax_amount' => round((float) $transaction->tax_amount, 4),
            'discount_amount' => round((float) $transaction->discount_amount, 4),
            'occurred_at' => Carbon::parse($transaction->transaction_date)->toIso8601String(),
            'updated_at' => optional($transaction->updated_at)->toIso8601String(),
            'contact' => $transaction->relationLoaded('contact') && $transaction->contact ? [
                'id' => (int) $transaction->contact->id,
                'name' => $transaction->contact->supplier_business_name ?: $transaction->contact->name,
                'mobile' => $transaction->contact->mobile,
            ] : null,
            'location' => $transaction->relationLoaded('location') && $transaction->location ? [
                'id' => (int) $transaction->location->id,
                'name' => $transaction->location->name,
            ] : null,
            'user' => $transaction->relationLoaded('sales_person') && $transaction->sales_person ? [
                'id' => (int) $transaction->sales_person->id,
                'name' => trim($transaction->sales_person->first_name.' '.$transaction->sales_person->last_name),
            ] : null,
        ];
    }

    public function detail(Transaction $transaction): array
    {
        $data = $this->summary($transaction);
        $data['notes'] = [
            'customer' => $transaction->additional_notes,
            'staff' => $transaction->staff_note,
            'shipping' => $transaction->shipping_details,
        ];
        $data['payments'] = $transaction->payment_lines->map(fn ($payment) => [
            'id' => (int) $payment->id,
            'reference' => $payment->payment_ref_no,
            'method' => $payment->method,
            'amount' => round((float) $payment->amount, 4),
            'is_return' => (bool) $payment->is_return,
            'paid_on' => ! empty($payment->paid_on)
                ? Carbon::parse($payment->paid_on)->toIso8601String()
                : null,
            'note' => $payment->note,
        ])->values();

        $data['sell_lines'] = $transaction->sell_lines->map(fn ($line) => [
            'id' => (int) $line->id,
            'product_id' => (int) $line->product_id,
            'variation_id' => (int) $line->variation_id,
            'product_name' => optional($line->product)->name,
            'variation_name' => optional($line->variations)->name,
            'sku' => optional($line->variations)->sub_sku,
            'quantity' => round((float) $line->quantity, 4),
            'quantity_returned' => round((float) $line->quantity_returned, 4),
            'unit_price' => round((float) $line->unit_price_inc_tax, 4),
            'line_total' => round(
                ((float) $line->quantity - (float) $line->quantity_returned) * (float) $line->unit_price_inc_tax,
                4
            ),
        ])->values();

        $data['purchase_lines'] = $transaction->purchase_lines->map(fn ($line) => [
            'id' => (int) $line->id,
            'product_id' => (int) $line->product_id,
            'variation_id' => (int) $line->variation_id,
            'product_name' => optional($line->product)->name,
            'variation_name' => optional($line->variations)->name,
            'sku' => optional($line->variations)->sub_sku,
            'quantity' => round((float) $line->quantity, 4),
            'quantity_returned' => round((float) $line->quantity_returned, 4),
            'unit_price' => round((float) $line->purchase_price_inc_tax, 4),
            'line_total' => round((float) $line->quantity * (float) $line->purchase_price_inc_tax, 4),
            'lot_number' => $line->lot_number,
            'expiry_date' => $line->exp_date,
        ])->values();

        return $data;
    }
}
