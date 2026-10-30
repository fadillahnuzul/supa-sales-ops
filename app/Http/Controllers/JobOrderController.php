<?php

namespace App\Http\Controllers;

use App\Services\InquiryPrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class JobOrderController extends Controller
{
    public function __construct(private InquiryPrintService $inquiryPrintService) {}

    private function roleId(Request $request): int
    {
        $user = $request->user();
        $id = $user->role->role_id ?? $user->role?->id ?? null;
        abort_unless($id && in_array((int) $id, [1, 2, 3, 4, 5], true), 403, 'Tidak memiliki akses Job Order.');

        return (int) $id;
    }

    private function admin(Request $request): void
    {
        abort_unless(in_array($this->roleId($request), [1, 4], true), 403);
    }

    private function approver(Request $request): void
    {
        abort_unless(in_array($this->roleId($request), [2, 3, 5], true), 403);
    }

    /** Scope by employees' segmentation assignment (adapt if role All overrides this). */
    private function segmentationIds(Request $request): array
    {
        return DB::table('core.employee_segmentations')
            ->where('employee_id', $request->user()->id)
            ->pluck('segmentation_id')->all();
    }

    private function visibleInquiries(Request $request)
    {
        $role = $this->roleId($request);

        $query = DB::table('sales.inquiries as i')
            ->whereNull('i.deleted_at');

        if ($role === 4 || $role === 5) {
            return $query;
        }

        $employeeId = $request->user()->id;
        $segmentationIds = $this->segmentationIds($request);

        return $query->where(function ($q) use (
            $employeeId,
            $segmentationIds
        ) {
            $q->where('i.pic', $employeeId);

            if (! empty($segmentationIds)) {
                $q->orWhereExists(function ($subquery) use (
                    $segmentationIds
                ) {
                    $subquery
                        ->selectRaw('1')
                        ->from('core.employee_segmentations as es')
                        ->whereColumn('es.employee_id', 'i.pic')
                        ->whereIn('es.segmentation_id', $segmentationIds);
                });
            }
        });
    }

    private function accessibleJo(Request $request, int $id)
    {
        return DB::table('sales.job_orders as jo')
            ->join('sales.inquiries as i', 'i.id', '=', 'jo.inquiry_id')
            ->where('jo.id', $id)
            ->where(function () use ($request) {
                if ($this->roleId($request) !== 5) {

                }
            })
            ->whereIn('jo.inquiry_id', $this->visibleInquiries($request)->select('i.id'))
            ->select('jo.*')->firstOrFail();
    }

    public function page(Request $request)
    {
        return Inertia::render('JobOrderCenter', ['roleId' => $this->roleId($request)]);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);
        $start = $filters['start_date'] ?? now()->startOfMonth()->toDateString();
        $end = $filters['end_date'] ?? now()->endOfMonth()->toDateString();
        $search = trim($filters['search'] ?? '');

        $inquiries = $this->visibleInquiries($request)
            ->leftJoin(
                'core.customers as c',
                'c.id',
                '=',
                'i.customer_id'
            )
            ->leftJoin(
                'sales.job_orders as jo',
                'jo.inquiry_id',
                '=',
                'i.id'
            )
            ->whereDate('i.date', '>=', $start)
            ->whereDate('i.date', '<=', $end)

            ->select([
                'i.id as inquiry_id',
                'i.date as inquiry_date',
                'i.code as inquiry_code',
                'c.name as customer_name',
                'jo.id as job_order_id',
                'jo.so_no',
                'jo.po_no',
                'jo.status',
            ])

            ->orderByDesc('i.date')
            ->orderByDesc('i.id')
            ->paginate(30);

        return response()->json($inquiries);
    }

    public function showInquiry(Request $request, int $id)
    {
        $this->admin($request);
        abort_unless($this->visibleInquiries($request)->where('i.id', $id)->exists(), 404);
        abort_if(DB::table('sales.job_orders')->where('inquiry_id', $id)->exists(), 409, 'JO sudah ada; pilih dari daftar JO.');
        $data = $this->inquiryPrintService->getFullInquiry($id);
        $inquiry = $data['inquiry'];

        return response()->json([
            'inquiry_id' => $id,
            'date' => now()->toDateString(),
            'so_no' => '',
            'po_no' => '',
            'customer_id' => $inquiry->customer_id ?? null,
            'customer_no' => $inquiry->customer_no ?? $inquiry->customer_code ?? '',
            'customer_name' => $inquiry->customer_name ?? '',
            'customer_address' => $inquiry->customer_address ?? '',
            'pic_id' => $inquiry->pic ?? null,
            'pic_name' => $inquiry->pic_name ?? '',
            'shipping_date' => $inquiry->etd ?? null,
            'details' => collect($data['details'])->values()->map(fn ($d, $i) => [
                'inquiry_detail_id' => $d->id ?? null,
                'kg' => $d->qty ?? 0,
                'product_code' => $d->product_code ?? '',
                'product_name' => $d->product_name ?? '',
                'packing' => '',
                'note' => $d->note ?? '',
                'sort_order' => $i,
            ]),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $jo = $this->accessibleJo($request, $id);
        $jo->details = DB::table('sales.job_order_details')->where('job_order_id', $id)
            ->orderBy('sort_order')->orderBy('id')->get();

        return response()->json($jo);
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'inquiry_id' => [
                $id ? 'sometimes' : 'required',
                'integer',
                Rule::exists(DB::connection()->getName().'.sales.inquiries', 'id'),
            ],
            'date' => ['required', 'date_format:Y-m-d'],
            'so_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique(DB::connection()->getName().'.sales.job_orders', 'so_no')->ignore($id),
            ],
            'po_no' => ['required', 'string', 'max:100'],
            'customer_no' => ['nullable', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_address' => ['nullable', 'string'],
            'shipping_date' => ['nullable', 'date_format:Y-m-d'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.inquiry_detail_id' => ['nullable', 'integer'],
            'details.*.kg' => ['required', 'numeric', 'min:0'],
            'details.*.product_code' => ['nullable', 'string', 'max:100'],
            'details.*.product_name' => ['required', 'string', 'max:255'],
            'details.*.packing' => ['nullable', 'string', 'max:255'],
            'details.*.note' => ['nullable', 'string'],
        ]);
    }

    private function saveDetails(int $joId, array $details): void
    {
        DB::table('sales.job_order_details')->where('job_order_id', $joId)->delete();
        foreach ($details as $position => $d) {
            DB::table('sales.job_order_details')->insert([
                'job_order_id' => $joId,
                'inquiry_detail_id' => $d['inquiry_detail_id'] ?? null,
                'kg' => $d['kg'],
                'product_code' => $d['product_code'] ?? null,
                'product_name' => $d['product_name'],
                'packing' => $d['packing'] ?? null,
                'note' => $d['note'] ?? null,
                'sort_order' => $position,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function store(Request $request)
    {
        $this->admin($request);
        $data = $this->validated($request);
        abort_unless($this->visibleInquiries($request)->where('i.id', $data['inquiry_id'])->exists(), 403);
        $id = DB::transaction(function () use ($request, $data) {
            $inquiry = DB::table('sales.inquiries')->where('id', $data['inquiry_id'])->lockForUpdate()->first();
            abort_unless($inquiry, 404);
            abort_if(DB::table('sales.job_orders')->where('inquiry_id', $inquiry->id)->exists(), 409, 'JO sudah ada.');
            $id = DB::table('sales.job_orders')->insertGetId([
                'inquiry_id' => $inquiry->id,
                'date' => $data['date'],
                'so_no' => $data['so_no'],
                'po_no' => $data['po_no'],
                'customer_id' => $inquiry->customer_id,
                'customer_no' => $data['customer_no'] ?? null,
                'customer_name' => $data['customer_name'],
                'customer_address' => $data['customer_address'] ?? null,
                'pic_id' => $inquiry->pic,
                'shipping_date' => $data['shipping_date'] ?? null,
                'status' => 'pending',
                'created_by' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'id');
            $this->saveDetails($id, $data['details']);

            return $id;
        });

        return response()->json(['id' => $id, 'message' => 'JO berhasil dibuat'], 201);
    }

    public function update(Request $request, int $id)
    {
        $this->admin($request);
        $this->accessibleJo($request, $id);
        $data = $this->validated($request, $id);
        DB::transaction(function () use ($id, $data, $request) {
            $jo = DB::table('sales.job_orders')->where('id', $id)->lockForUpdate()->first();
            abort_if($jo->status === 'approved' || $jo->status === 'checked', 409, 'JO yang sudah approved tidak dapat diubah.');
            DB::table('sales.job_orders')->where('id', $id)->update([
                'date' => $data['date'],
                'so_no' => $data['so_no'],
                'po_no' => $data['po_no'],
                'customer_no' => $data['customer_no'] ?? null,
                'customer_name' => $data['customer_name'],
                'customer_address' => $data['customer_address'] ?? null,
                'shipping_date' => $data['shipping_date'] ?? null,
                'updated_by' => $request->user()->id,
                'updated_at' => now(),
            ]);
            $this->saveDetails($id, $data['details']);
        });

        return response()->json(['id' => $id, 'message' => 'JO berhasil diperbarui']);
    }

    public function check(Request $request, int $id)
    {
        $this->accessibleJo($request, $id);
        $count = DB::table('sales.job_orders')->where('id', $id)->where('status', 'pending')->update([
            'status' => 'checked',
            'checked_by' => $request->user()->id,
            'checked_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['approved_count' => $count]);
    }

    public function bulkCheck(Request $request)
    {
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:100'], 'ids.*' => ['integer', 'distinct']]);
        $count = DB::table('sales.job_orders')->whereIn('id', $data['ids'])->where('status', 'pending')
            ->whereIn('inquiry_id', $this->visibleInquiries($request)->select('i.id'))
            ->update([
                'status' => 'checked',
                'checked_by' => $request->user()->id,
                'checked_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json(['approved_count' => $count]);
    }

    public function approve(Request $request, int $id)
    {
        $this->approver($request);
        $this->accessibleJo($request, $id);
        $count = DB::table('sales.job_orders')->where('id', $id)->where('status', 'checked')->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['approved_count' => $count]);
    }

    public function bulkApprove(Request $request)
    {
        $this->approver($request);
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:100'], 'ids.*' => ['integer', 'distinct']]);
        $count = DB::table('sales.job_orders')->whereIn('id', $data['ids'])->where('status', 'checked')
            ->whereIn('inquiry_id', $this->visibleInquiries($request)->select('i.id'))
            ->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json(['approved_count' => $count]);
    }

    public function print(Request $request, int $id)
    {
        $this->admin($request);
        $jo = $this->accessibleJo($request, $id);
        $details = DB::table('sales.job_order_details')->where('job_order_id', $id)->orderBy('sort_order')->get();
        $signerIds = collect([$jo->created_by, $jo->checked_by, $jo->approved_by])
            ->filter()
            ->unique()
            ->values();
        $signers = DB::table('core.employees')
            ->whereIn('id', $signerIds)
            ->get(['id', 'first_name', 'last_name'])
            ->keyBy('id');
        $signatureNames = [
            'created_by' => $this->employeeName($signers->get($jo->created_by)),
            'checked_by' => $this->employeeName($signers->get($jo->checked_by)),
            'approved_by' => $this->employeeName($signers->get($jo->approved_by)),
        ];
        $signatureRecords = DB::table('sales.sales_signatures')
            ->whereIn('user_id', $signerIds)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');
        $signatureImages = collect([
            'created_by' => $jo->created_by,
            'checked_by' => $jo->checked_by,
            'approved_by' => $jo->approved_by,
        ])->mapWithKeys(function ($userId, $role) use ($signatureRecords): array {
            $signature = $signatureRecords->get($userId);

            return [
                $role => $signature
                    ? route('signature-settings.file', $signature->id)
                    : null,
            ];
        });

        return view('job-orders.print', compact('jo', 'details', 'signatureNames', 'signatureImages'));
    }

    private function employeeName(?object $employee): ?string
    {
        if (! $employee) {
            return null;
        }

        return trim($employee->first_name.' '.$employee->last_name);
    }
}
