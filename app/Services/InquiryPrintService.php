<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InquiryPrintService
{
    public function getInquiry(int $id): ?object
    {
        return DB::table('sales.inquiries as i')
            ->leftJoin(
                'core.customers as c',
                'c.id',
                '=',
                'i.customer_id'
            )
            ->select([
                'i.id',
                'i.code',
                'i.date',
                'i.customer_id',
                'i.pic',
                'i.shipping_rate',

                'c.name as customer_name',
                'c.address as customer_address',
                'c.pic as customer_pic',
                'c.phone as customer_phone',
                'c.divisi as segmentation',
                'c.level',
            ])
            ->where('i.id', $id)
            ->first();
    }

    public function getDetails(int $inquiryId)
    {
        return DB::table('sales.inquiry_details as d')
            ->leftJoin(
                'core.products as p',
                'p.id',
                '=',
                'd.product_id'
            )
            ->leftJoin(
                'core.grades as g',
                'g.id',
                '=',
                'd.grade_id'
            )
            ->select([
                'd.id',
                'd.inquiry_id',
                'd.product_id',

                'p.name as product_name',
                'p.code as product_code',

                'g.name as grade_name',

                'd.qty',

                'd.recommended_price',
                'd.approved_price',
                'd.approved_date',

                'd.offer_1_price',
                'd.offer_2_price',
                'd.offer_3_price',
                'd.final_price',

                'd.note',
            ])
            ->where('d.inquiry_id', $inquiryId)
            ->orderBy('d.id')
            ->get();
    }

    public function getFullInquiry(int $id): array
    {
        $inquiry = $this->getInquiry($id);

        if (! $inquiry) {
            abort(404, 'Inquiry tidak ditemukan.');
        }

        $details = $this->getDetails($id);

        return [
            'inquiry' => $inquiry,
            'details' => $details,
        ];
    }
}
