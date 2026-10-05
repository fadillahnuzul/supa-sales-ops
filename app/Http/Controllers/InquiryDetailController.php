<?php

namespace App\Http\Controllers;

use App\Models\Core\ProductModel;
use App\Models\Core\GradeModel;
use App\Models\Sales\CompetitorModel;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InquiryDetailController extends Controller
{
    public function store(
        Request $request,
        int $inquiry
    ) {
        $existingInquiry =
            DB::table(
                'sales.inquiries'
            )
                ->where(
                    'id',
                    $inquiry
                )
                ->whereNull(
                    'deleted_at'
                )
                ->exists();

        abort_unless(
            $existingInquiry,
            404
        );

        $validated =
            $this->validateDetail(
                $request
            );

        /*
        |--------------------------------------------------------------------------
        | Product Standard Price
        |--------------------------------------------------------------------------
        */

        $productStdPrice =
            $this->getProductStdPrice(
                $validated[
                    'product_id'
                ]
            );


        $alternativePrice =
            $this->getAlternativePrice(
                $validated[
                    'source_ap'
                ] ?? null,

                $validated[
                    'product_id'
                ]
            );

        DB::table(
            'sales.inquiry_details'
        )->insert([
            'inquiry_id' =>
                $inquiry,

            'product_id' =>
                $validated[
                    'product_id'
                ],

            'grade_id' =>
                $validated[
                    'grade_id'
                ] ?? null,

            'qty' =>
                $validated[
                    'qty'
                ] ?? null,

            'product_std_price' =>
                $productStdPrice,

            'alternative_price' =>
                $alternativePrice,

            'source_ap' =>
                $validated[
                    'source_ap'
                ] ?? null,

            'date_ap' =>
                $validated[
                    'date_ap'
                ] ?? null,

            'reference_price' =>
                $validated[
                    'reference_price'
                ] ?? null,

            'last_order_date' =>
                $validated[
                    'last_order_date'
                ] ?? null,

            'last_order_price' =>
                $validated[
                    'last_order_price'
                ] ?? null,

            'last_quotation_date' =>
                $validated[
                    'last_quotation_date'
                ] ?? null,

            'last_quotation_price' =>
                $validated[
                    'last_quotation_price'
                ] ?? null,

            'recommended_price' =>
                $validated[
                    'recommended_price'
                ] ?? null,

            'approved_price' =>
                $validated[
                    'approved_price'
                ] ?? null,

            'approved_date' =>
                $validated[
                    'approved_date'
                ] ?? null,

            'offer_1_price' =>
                $validated[
                    'offer_1_price'
                ] ?? null,

            'offer_2_price' =>
                $validated[
                    'offer_2_price'
                ] ?? null,

            'offer_3_price' =>
                $validated[
                    'offer_3_price'
                ] ?? null,

            'final_price' =>
                $validated[
                    'final_price'
                ] ?? null,

            'note' =>
                $validated[
                    'note'
                ] ?? null,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);

        return back()->with(
            'success',
            'Detail inquiry berhasil ditambahkan.'
        );
    }

    /**
     * Update detail.
     */
    public function update(
        Request $request,
        int $detail
    ) {
        $existing =
            DB::table(
                'sales.inquiry_details'
            )
                ->where(
                    'id',
                    $detail
                )
                ->whereNull(
                    'deleted_at'
                )
                ->first();

        abort_if(
            !$existing,
            404
        );

        $validated =
            $this->validateDetail(
                $request
            );

        /*
        |--------------------------------------------------------------------------
        | Refresh Standard Price
        |--------------------------------------------------------------------------
        */

        $productStdPrice =
            $this->getProductStdPrice(
                $validated[
                    'product_id'
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Refresh Alternative Price
        |--------------------------------------------------------------------------
        |
        | Kalau product / source AP berubah,
        | harga competitor juga otomatis berubah.
        |
        */

        $alternativePrice =
            $this->getAlternativePrice(
                $validated[
                    'source_ap'
                ] ?? null,

                $validated[
                    'product_id'
                ]
            );

        DB::table(
            'sales.inquiry_details'
        )
            ->where(
                'id',
                $detail
            )
            ->update([
                'product_id' =>
                    $validated[
                        'product_id'
                    ],

                'grade_id' =>
                    $validated[
                        'grade_id'
                    ] ?? null,

                'qty' =>
                    $validated[
                        'qty'
                    ] ?? null,

                'product_std_price' =>
                    $productStdPrice,

                'alternative_price' =>
                    $alternativePrice,

                'source_ap' =>
                    $validated[
                        'source_ap'
                    ] ?? null,

                'date_ap' =>
                    $validated[
                        'date_ap'
                    ] ?? null,

                'reference_price' =>
                    $validated[
                        'reference_price'
                    ] ?? null,

                'last_order_date' =>
                    $validated[
                        'last_order_date'
                    ] ?? null,

                'last_order_price' =>
                    $validated[
                        'last_order_price'
                    ] ?? null,

                'last_quotation_date' =>
                    $validated[
                        'last_quotation_date'
                    ] ?? null,

                'last_quotation_price' =>
                    $validated[
                        'last_quotation_price'
                    ] ?? null,

                'recommended_price' =>
                    $validated[
                        'recommended_price'
                    ] ?? null,

                'approved_price' =>
                    $validated[
                        'approved_price'
                    ] ?? null,

                'approved_date' =>
                    $validated[
                        'approved_date'
                    ] ?? null,

                'offer_1_price' =>
                    $validated[
                        'offer_1_price'
                    ] ?? null,

                'offer_2_price' =>
                    $validated[
                        'offer_2_price'
                    ] ?? null,

                'offer_3_price' =>
                    $validated[
                        'offer_3_price'
                    ] ?? null,

                'final_price' =>
                    $validated[
                        'final_price'
                    ] ?? null,

                'note' =>
                    $validated[
                        'note'
                    ] ?? null,

                'updated_at' =>
                    now(),
            ]);

        return back()->with(
            'success',
            'Detail inquiry berhasil diperbarui.'
        );
    }

    /**
     * Soft delete detail.
     */
    public function destroy(
        int $detail
    ) {
        DB::table(
            'sales.inquiry_details'
        )
            ->where(
                'id',
                $detail
            )
            ->whereNull(
                'deleted_at'
            )
            ->update([
                'deleted_at' =>
                    now(),

                'updated_at' =>
                    now(),
            ]);

        return back()->with(
            'success',
            'Detail inquiry berhasil dihapus.'
        );
    }

    /**
     * Validasi detail inquiry.
     */
    private function validateDetail(
        Request $request
    ): array {
        return $request->validate([
            'product_id' => [
                'required',
                'integer',
                Rule::exists(ProductModel::class, 'id')
            ],

            'grade_id' => [
                'nullable',
                'integer',
                Rule::exists(GradeModel::class, 'id')
            ],

            'qty' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Source AP = Competitor ID
            |--------------------------------------------------------------------------
            */

            'source_ap' => [
                'nullable',
                'integer',
                Rule::exists(CompetitorModel::class, 'id'),
            ],

            'date_ap' => [
                'nullable',
                'date',
            ],

            'reference_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'last_order_date' => [
                'nullable',
                'date',
            ],

            'last_order_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'last_quotation_date' => [
                'nullable',
                'date',
            ],

            'last_quotation_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'recommended_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'approved_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'approved_date' => [
                'nullable',
                'date',
            ],

            'offer_1_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'offer_2_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'offer_3_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'final_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'note' => [
                'nullable',
                'string',
            ],
        ]);
    }

    /**
     * Ambil standard price Product.
     */
    private function getProductStdPrice(
        int $productId
    ): ?float {
        $price =
            DB::table('core.products')
                ->where(
                    'id',
                    $productId
                )
                ->value(
                    'std_price'
                );

        return $price !== null
            ? (float) $price
            : null;
    }

    private function getAlternativePrice(
        ?int $competitorId,
        int $productId
    ): ?float {
        if (!$competitorId) {
            return null;
        }

        $price =
            DB::table(
                'sales.competitor_products'
            )
                ->where(
                    'competitor_id',
                    $competitorId
                )
                ->where(
                    'product_id',
                    $productId
                )
                ->whereNull(
                    'deleted_at'
                )
                ->orderByDesc(
                    'date'
                )
                ->orderByDesc(
                    'id'
                )
                ->value(
                    'price'
                );

        return $price !== null
            ? (float) $price
            : null;
    }
}