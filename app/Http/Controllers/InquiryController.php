<?php

namespace App\Http\Controllers;

use App\Models\Core\CustomerModel;
use App\Models\Core\EmployeeModel;
use App\Models\Core\SegmentationModel;
use App\Models\Sales\InquiryModel;
use App\Services\InquiryCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    public function __construct(private InquiryCodeService $inquiryCodeService) {}

    private function segmentationIds(): array
    {
        $employeeId = auth()->id();

        if (! $employeeId) {
            return [];
        }

        return DB::table('core.employee_segmentations')
            ->where('employee_id', $employeeId)
            ->pluck('segmentation_id')
            ->all();
    }

    public function index(): Response
    {
        $segmentationIds = $this->segmentationIds();

        $inquiries = DB::table('sales.inquiries as i')
            ->leftJoin(
                'core.customers as c',
                'c.id',
                '=',
                'i.customer_id'
            )
            ->leftJoin(
                'core.employees as e',
                'e.id',
                '=',
                'i.pic'
            )
            ->whereNull('i.deleted_at');

        if (! auth()->user()->hasAnyRole([3])) {
            $inquiries->whereIn('i.segmentation_id', $segmentationIds);
        }

        $inquiries = $inquiries
            ->select([
                'i.id',
                'i.code',
                'i.date',
                'i.etd',
                'i.pic',
                'i.customer_id',
                'i.segmentation_id',
                'i.shipping_rate',
                'i.note',

                'c.name as customer_name',
                'c.divisi as customer_division',
                'c.level as customer_risk_level',
                's.name as segmentation_name',

                DB::raw('concat(e.first_name) as pic_name'),
            ])
            ->leftJoin(
                'core.segmentations as s',
                's.id',
                '=',
                'i.segmentation_id'
            )
            ->orderByDesc('i.date')
            ->orderByDesc('i.id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Detail Inquiry
        |--------------------------------------------------------------------------
        */

        $inquiryIds = $inquiries
            ->pluck('id')
            ->all();

        $details = collect();

        $latestCompetitorPrices = DB::table(
            'sales.competitor_products as cp'
        )
            ->whereNull(
                'cp.deleted_at'
            )
            ->selectRaw('
        DISTINCT ON (
            cp.competitor_id,
            cp.product_id
        )

        cp.id,
        cp.competitor_id,
        cp.product_id,
        cp.price,
        cp.date,
        cp.note
    ')
            ->orderBy(
                'cp.competitor_id'
            )
            ->orderBy(
                'cp.product_id'
            )
            ->orderByDesc(
                'cp.date'
            )
            ->orderByDesc(
                'cp.id'
            );

        if (! empty($inquiryIds)) {
            $details = DB::table(
                'sales.inquiry_details as d'
            )
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

                ->leftJoin(
                    'sales.competitors as competitor',
                    'competitor.id',
                    '=',
                    'd.source_ap'
                )

                ->leftJoinSub(
                    $latestCompetitorPrices,
                    'cp',
                    function ($join) {
                        $join
                            ->on(
                                'cp.competitor_id',
                                '=',
                                'd.source_ap'
                            )
                            ->on(
                                'cp.product_id',
                                '=',
                                'd.product_id'
                            );
                    }
                )

                ->whereIn(
                    'd.inquiry_id',
                    $inquiryIds
                )

                ->whereNull(
                    'd.deleted_at'
                )

                ->select([
                    'd.id',
                    'd.inquiry_id',

                    'd.product_id',
                    'd.grade_id',

                    'd.qty',

                    'd.product_std_price',

                    'cp.price as alternative_price',

                    'd.source_ap',

                    'cp.date as date_ap',

                    'd.reference_price',

                    'd.last_order_date',
                    'd.last_order_price',

                    'd.last_quotation_date',
                    'd.last_quotation_price',

                    'd.recommended_price',

                    'd.approved_price',
                    'd.approved_date',

                    'd.offer_1_price',
                    'd.offer_2_price',
                    'd.offer_3_price',

                    'd.final_price',

                    'd.note',

                    'p.name as product_name',
                    'p.code as product_code',
                    'p.std_price as current_std_price',

                    'g.name as grade_name',

                    'competitor.name as competitor_name',

                    'cp.id as competitor_product_id',
                    'cp.price as competitor_price',
                    'cp.date as competitor_price_date',
                ])

                ->orderBy(
                    'd.id'
                )

                ->get()

                ->groupBy(
                    'inquiry_id'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Mapping Inquiry
        |--------------------------------------------------------------------------
        */

        $inquiryData = $inquiries
            ->map(function ($inquiry) use ($details) {
                $detailRows =
                    $details->get(
                        $inquiry->id,
                        collect()
                    );

                return [
                    'id' => $inquiry->id,

                    'code' => $inquiry->code,

                    'date' => $inquiry->date,

                    'etd' => $inquiry->etd,

                    'pic' => $inquiry->pic,

                    'customerId' => $inquiry->customer_id,

                    'segmentationId' => $inquiry->segmentation_id,

                    'segmentation' => $inquiry->segmentation_name,

                    'shippingRate' => $inquiry->shipping_rate !== null
                        ? (float) $inquiry->shipping_rate
                        : null,

                    'note' => $inquiry->note,

                    /*
                    | Customer Relation
                    */
                    'customer' => $inquiry->customer_id
                        ? [
                            'id' => $inquiry->customer_id,

                            'name' => $inquiry->customer_name,

                            'division' => $inquiry->customer_division,

                            'riskLevel' => $inquiry->customer_risk_level,
                        ]
                        : null,

                    /*
                    | PIC Relation
                    */
                    'picUser' => $inquiry->pic
                        ? [
                            'id' => $inquiry->pic,

                            'name' => $inquiry->pic_name,
                        ]
                        : null,

                    /*
                    | Detail
                    */
                    'details' => $detailRows
                        ->map(
                            fn ($detail) => [
                                'id' => $detail->id,

                                'productId' => $detail->product_id,

                                'gradeId' => $detail->grade_id,

                                'qty' => $detail->qty !== null
                                    ? (float) $detail->qty
                                    : '',

                                'productStdPrice' => $detail->product_std_price !== null
                                    ? (float) $detail->product_std_price
                                    : null,

                                'alternativePrice' => $detail->alternative_price !== null
                                    ? (float) $detail->alternative_price
                                    : null,

                                'sourceAP' => $detail->source_ap,

                                'dateAP' => $detail->date_ap,

                                'referencePrice' => $detail->reference_price !== null
                                    ? (float) $detail->reference_price
                                    : null,

                                'lastOrderDate' => $detail->last_order_date,

                                'lastOrderPrice' => $detail->last_order_price !== null
                                    ? (float) $detail->last_order_price
                                    : null,

                                'lastQuotationDate' => $detail->last_quotation_date,

                                'lastQuotationPrice' => $detail->last_quotation_price !== null
                                    ? (float) $detail->last_quotation_price
                                    : null,

                                'recommendedPrice' => $detail->recommended_price !== null
                                    ? (float) $detail->recommended_price
                                    : null,

                                'approvedPrice' => $detail->approved_price !== null
                                    ? (float) $detail->approved_price
                                    : null,

                                'approvedDate' => $detail->approved_date,

                                'offer1Price' => $detail->offer_1_price !== null
                                    ? (float) $detail->offer_1_price
                                    : null,

                                'offer2Price' => $detail->offer_2_price !== null
                                    ? (float) $detail->offer_2_price
                                    : null,

                                'offer3Price' => $detail->offer_3_price !== null
                                    ? (float) $detail->offer_3_price
                                    : null,

                                'finalPrice' => $detail->final_price !== null
                                    ? (float) $detail->final_price
                                    : null,

                                'note' => $detail->note ?? '',

                                /*
                                    | Display Relation
                                    */
                                'product' => $detail->product_id
                                    ? [
                                        'id' => $detail->product_id,

                                        'name' => $detail->product_name,

                                        'code' => $detail->product_code,
                                    ]
                                    : null,

                                'grade' => $detail->grade_id
                                    ? [
                                        'id' => $detail->grade_id,

                                        'name' => $detail->grade_name,
                                    ]
                                    : null,

                                'competitor' => $detail->source_ap
                                    ? [
                                        'id' => $detail->source_ap,

                                        'name' => $detail->competitor_name,
                                    ]
                                    : null,

                                'isNew' => false,
                            ]
                        )
                        ->values(),
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Customer Options
        |--------------------------------------------------------------------------
        */

        $customers = DB::table('core.customers as c')
            ->select([
                'c.id',
                'c.code',
                'c.name',
                'c.divisi',
                'c.level',
            ])
            ->orderBy('c.name')
            ->get()
            ->map(
                fn ($customer) => [
                    'id' => $customer->id,
                    'code' => $customer->code,
                    'name' => $customer->name,
                    'division' => $customer->divisi,
                    'riskLevel' => $customer->level,
                ]
            );

        $segmentations = SegmentationModel::query()
            ->when(
                ! auth()->user()->hasAnyRole([3]),
                fn ($query) => $query->whereIn('id', $segmentationIds)
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        $pics = EmployeeModel::query()
            ->with('position', 'position.position', 'userRoles.role')
            ->where(function ($query) {
                $query->whereHas('userRoles', function ($query) {
                    $query->where('role_id', '>', 1);
                })
                    ->orWhereHas('position.position', function ($query) {
                        $query->whereIn('division_id', [2]);
                    });
            })
            ->select([
                'id',
                DB::raw("CONCAT(first_name, ' ', last_name) as name"),
            ])
            ->distinct()
            ->orderBy('name')
            ->get();

        $products = DB::table('core.products')
            ->select([
                'id',
                'name',
                'code',
                'std_price',
            ])
            ->orderBy('name')
            ->get()
            ->map(
                fn ($product) => [
                    'id' => $product->id,

                    'name' => $product->name,

                    'code' => $product->code,

                    'stdPrice' => $product->std_price !== null
                        ? (float) $product->std_price
                        : null,
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Grade Options
        |--------------------------------------------------------------------------
        */

        $grades = DB::table('core.grades')
            ->select([
                'id',
                'name',
            ])
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Competitor / Source AP Options
        |--------------------------------------------------------------------------
        */

        $competitors = DB::table(
            'sales.competitors'
        )
            ->whereNull('deleted_at')
            ->select([
                'id',
                'name',
                'divisi',
            ])
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Competitor Price
        |--------------------------------------------------------------------------
        |
        | Digunakan frontend supaya Alternative Price
        | langsung berubah ketika Product + Source AP dipilih.
        |
        */

        $competitorPrices = DB::table(
            'sales.competitor_products as cp'
        )
            ->join(
                'sales.competitors as c',
                'c.id',
                '=',
                'cp.competitor_id'
            )
            ->whereNull(
                'cp.deleted_at'
            )
            ->whereNull(
                'c.deleted_at'
            )
            ->selectRaw('
        DISTINCT ON (
            cp.competitor_id,
            cp.product_id
        )

        cp.id,
        cp.competitor_id,
        cp.product_id,
        cp.price,
        cp.date,
        cp.note,

        c.name as competitor_name
    ')
            ->orderBy(
                'cp.competitor_id'
            )
            ->orderBy(
                'cp.product_id'
            )
            ->orderByDesc(
                'cp.date'
            )
            ->orderByDesc(
                'cp.id'
            )
            ->get()
            ->map(
                fn ($price) => [
                    'id' => $price->id,

                    'competitorId' => $price->competitor_id,

                    'competitorName' => $price->competitor_name,

                    'productId' => $price->product_id,

                    'price' => $price->price !== null
                        ? (float) $price->price
                        : null,

                    'date' => $price->date,

                    'note' => $price->note,
                ]
            );

        return Inertia::render(
            'Inquiry',
            [
                'inquiries' => $inquiryData,

                'customers' => $customers,

                'segmentations' => $segmentations,

                'pics' => $pics,

                'products' => $products,

                'grades' => $grades,

                'competitors' => $competitors,

                'competitorPrices' => $competitorPrices,
            ]
        );
    }

    public function reserveCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'pic' => [
                'required',
                'integer',
                Rule::exists(EmployeeModel::class, 'id'),
            ],
        ]);

        $code = DB::transaction(fn (): string => $this->inquiryCodeService->reserve(
            (int) $validated['pic'],
            $validated['date']
        ));

        return response()->json(['code' => $code]);
    }

    public function store(Request $request)
    {
        $segmentationIds = $this->segmentationIds();

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:100',
            ],

            'date' => [
                'required',
                'date',
            ],

            'etd' => [
                'nullable',
                'date',
            ],

            'pic' => [
                'required',
                'integer',
                Rule::exists(EmployeeModel::class, 'id'),
            ],

            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists(CustomerModel::class, 'id'),
            ],

            'segmentation_id' => [
                'nullable',
                'integer',
                Rule::exists(SegmentationModel::class, 'id')
                    ->where(fn ($query) => $query->whereIn('id', $segmentationIds)),
            ],

            'shipping_rate' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'note' => [
                'nullable',
                'string',
            ],
        ]);

        DB::transaction(function () use ($validated): void {
            $this->inquiryCodeService->consume(
                (int) $validated['pic'],
                $validated['date'],
                $validated['code']
            );

            DB::table('sales.inquiries')->insert([
                'code' => $validated['code'],
                'date' => $validated['date'],
                'etd' => $validated['etd'] ?? null,
                'pic' => $validated['pic'],
                'customer_id' => $validated['customer_id'] ?? null,
                'segmentation_id' => $validated['segmentation_id'] ?? null,
                'shipping_rate' => $validated['shipping_rate'] ?? null,
                'note' => $validated['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with(
            'success',
            'Inquiry berhasil dibuat.'
        );
    }

    /**
     * Update Inquiry Header.
     */
    public function update(
        Request $request,
        int $inquiry
    ) {
        $segmentationIds = $this->segmentationIds();

        $existing =
            DB::table('sales.inquiries')
                ->where('id', $inquiry)
                ->whereNull('deleted_at')
                ->first();

        abort_if(
            ! $existing,
            404
        );

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:100',

                Rule::unique(
                    InquiryModel::class,
                    'code'
                )
                    ->ignore(
                        $inquiry
                    )
                    ->whereNull(
                        'deleted_at'
                    ),
            ],

            'date' => [
                'required',
                'date',
            ],

            'etd' => [
                'nullable',
                'date',
            ],

            'pic' => [
                'nullable',
                'integer',
                Rule::exists(EmployeeModel::class, 'id'),
            ],

            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists(CustomerModel::class, 'id'),
            ],

            'segmentation_id' => [
                'nullable',
                'integer',
                Rule::exists(SegmentationModel::class, 'id')
                    ->where(fn ($query) => $query->whereIn('id', $segmentationIds)),
            ],

            'shipping_rate' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'note' => [
                'nullable',
                'string',
            ],
        ]);

        DB::table(
            'sales.inquiries'
        )
            ->where(
                'id',
                $inquiry
            )
            ->update([
                'code' => $validated['code'],

                'date' => $validated['date'],

                'etd' => $validated['etd'] ?? null,

                'pic' => $validated['pic'] ?? null,

                'customer_id' => $validated['customer_id'] ?? null,

                'segmentation_id' => $validated['segmentation_id'] ?? null,

                'shipping_rate' => $validated['shipping_rate'] ?? null,

                'note' => $validated['note'] ?? null,

                'updated_at' => now(),
            ]);

        return back()->with(
            'success',
            'Inquiry berhasil diperbarui.'
        );
    }

    /**
     * Soft Delete Inquiry.
     */
    public function destroy(
        int $inquiry
    ) {
        DB::transaction(
            function () use ($inquiry) {
                DB::table(
                    'sales.inquiry_details'
                )
                    ->where(
                        'inquiry_id',
                        $inquiry
                    )
                    ->whereNull(
                        'deleted_at'
                    )
                    ->update([
                        'deleted_at' => now(),

                        'updated_at' => now(),
                    ]);

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
                    ->update([
                        'deleted_at' => now(),

                        'updated_at' => now(),
                    ]);
            }
        );

        return back()->with(
            'success',
            'Inquiry berhasil dihapus.'
        );
    }
}
