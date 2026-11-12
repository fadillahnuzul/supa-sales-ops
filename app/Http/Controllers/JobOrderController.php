<?php

namespace App\Http\Controllers;

use App\Models\Core\CustomerModel;
use App\Models\Core\EmployeeModel;
use App\Models\Core\ProductModel;
use App\Models\Sales\JobOrderModel;
use App\Services\InquiryPrintService;
use App\Support\SpreadsheetFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;

class JobOrderController extends Controller
{
    public function __construct(private InquiryPrintService $inquiryPrintService) {}

    /**
     * @return list<int>
     */
    private function roleIds(Request $request): array
    {
        $roleIds = array_values(array_intersect(
            $request->user()->roleIds(),
            [1, 2, 3, 4, 5]
        ));
        abort_unless($roleIds !== [], 403, 'Tidak memiliki akses Job Order.');

        return $roleIds;
    }

    private function admin(Request $request): void
    {
        abort_unless(
            array_intersect($this->roleIds($request), [1, 4]) !== [],
            403
        );
    }

    private function approver(Request $request): void
    {
        abort_unless(
            array_intersect($this->roleIds($request), [2, 3, 5]) !== [],
            403
        );
    }

    /** Return the segmentations assigned to the employee. */
    private function segmentationIds(Request $request): array
    {
        return DB::table('core.employee_segmentations')
            ->where('employee_id', $request->user()->id)
            ->pluck('segmentation_id')->all();
    }

    private function visibleInquiries(Request $request)
    {
        $query = DB::table('sales.inquiries as i')
            ->whereNull('i.deleted_at');

        if (array_intersect($this->roleIds($request), [3, 4, 5]) !== []) {
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
                $q->orWhereIn('i.segmentation_id', $segmentationIds);
            }
        });
    }

    private function visibleJobOrders(Request $request)
    {
        $query = DB::table('sales.job_orders as jo');

        if (array_intersect($this->roleIds($request), [3, 4, 5]) !== []) {
            return $query;
        }

        $employeeId = $request->user()->id;
        $segmentationIds = $this->segmentationIds($request);

        return $query->where(function ($q) use ($request, $employeeId, $segmentationIds) {
            $q->whereIn('jo.inquiry_id', $this->visibleInquiries($request)->select('i.id'))
                ->orWhere(function ($directOrders) use ($employeeId, $segmentationIds) {
                    $directOrders->whereNull('jo.inquiry_id')
                        ->where(function ($visibility) use ($employeeId, $segmentationIds) {
                            $visibility->where('jo.pic_id', $employeeId)
                                ->orWhere('jo.created_by', $employeeId);

                            if ($segmentationIds !== []) {
                                $visibility->orWhereExists(function ($customer) use ($segmentationIds) {
                                    $customer->selectRaw('1')
                                        ->from('core.customers as c')
                                        ->whereColumn('c.id', 'jo.customer_id')
                                        ->whereIn('c.segmentation_id', $segmentationIds);
                                });
                            }
                        });
                });
        });
    }

    private function accessibleJo(Request $request, int $id)
    {
        return $this->visibleJobOrders($request)
            ->where('jo.id', $id)
            ->select('jo.*')->firstOrFail();
    }

    public function page(Request $request)
    {
        $roleIds = $this->roleIds($request);
        $picOptions = [];

        if (array_intersect($roleIds, [1, 4]) !== []) {
            $picOptions = EmployeeModel::query()
                ->where(function ($query) {
                    $query->whereHas('userRoles', function ($query) {
                        $query->where('role_id', '>', 1);
                    })
                        ->orWhereHas('position.position', function ($query) {
                            $query->whereIn('division_id', [2]);
                        });
                })
                ->where('is_active', true)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn ($employee): array => [
                    'id' => $employee->id,
                    'name' => $this->employeeName($employee),
                ])
                ->all();
        }

        return Inertia::render('JobOrderCenter', [
            'roleIds' => $roleIds,
            'picOptions' => $picOptions,
        ]);
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
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($matches) use ($search) {
                    $term = '%'.$search.'%';
                    $matches->where('i.code', 'like', $term)
                        ->orWhere('jo.so_no', 'like', $term)
                        ->orWhere('jo.po_no', 'like', $term)
                        ->orWhere('c.name', 'like', $term);
                });
            })
            ->select([
                'i.id as inquiry_id',
                'i.date as inquiry_date',
                'i.code as inquiry_code',
                'c.name as customer_name',
                'jo.id as job_order_id',
                'jo.so_no',
                'jo.po_no',
                'jo.status',
                DB::raw("'inquiry' as source"),
            ]);

        $directOrders = $this->visibleJobOrders($request)
            ->leftJoin('core.customers as direct_customer', 'direct_customer.id', '=', 'jo.customer_id')
            ->whereNull('jo.inquiry_id')
            ->whereDate('jo.date', '>=', $start)
            ->whereDate('jo.date', '<=', $end)
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(function ($matches) use ($term) {
                    $matches->where('jo.so_no', 'like', $term)
                        ->orWhere('jo.po_no', 'like', $term)
                        ->orWhere('direct_customer.name', 'like', $term);
                });
            })
            ->select([
                DB::raw('NULL as inquiry_id'),
                'jo.date as inquiry_date',
                DB::raw("'PO' as inquiry_code"),
                'direct_customer.name as customer_name',
                'jo.id as job_order_id',
                'jo.so_no',
                'jo.po_no',
                'jo.status',
                DB::raw("'po' as source"),
            ]);

        $rows = DB::query()->fromSub($inquiries->unionAll($directOrders), 'job_order_rows')
            ->orderByDesc('inquiry_date')
            ->orderByDesc('job_order_id')
            ->orderByDesc('inquiry_id')
            ->paginate(30);

        return response()->json($rows);
    }

    public function customers(Request $request)
    {
        $this->admin($request);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($filters['search'] ?? '');

        $customers = DB::table('core.customers')
            ->select(['id', 'code', 'name', 'address'])
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.mb_strtolower($search).'%';
                $query->where(function ($matches) use ($term): void {
                    $matches->whereRaw('LOWER(code) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(name) LIKE ?', [$term]);
                });
            })
            ->orderBy('name')
            ->limit(30)
            ->get();

        return response()->json($customers);
    }

    public function products(Request $request)
    {
        $this->admin($request);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($filters['search'] ?? '');

        $products = ProductModel::query()
            ->select(['id', 'code', 'name'])
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.mb_strtolower($search).'%';
                $query->where(function ($matches) use ($term): void {
                    $matches->whereRaw('LOWER(code) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(name) LIKE ?', [$term]);
                });
            })
            ->orderBy('name')
            ->limit(30)
            ->get();

        return response()->json($products);
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
        $accessibleJo = $this->accessibleJo($request, $id);
        $jo = JobOrderModel::query()
            ->with([
                'customer',
                'picEmployee',
                'createdByEmployee',
                'checkedByEmployee',
                'approvedByEmployee',
                'details',
            ])
            ->findOrFail($accessibleJo->id);

        return response()->json($jo);
    }

    public function template(Request $request)
    {
        $this->admin($request);
        $sheet = SpreadsheetFile::buildXlsx([[
            'date',
            'so_no',
            'po_no',
            'customer_no',
            'customer_name',
            'pic_name',
            'shipping_date',
            'kg',
            'product_code',
            'product_name',
            'packing',
            'note',
        ]], 'Job Order Import');

        return response()->streamDownload(function () use ($sheet): void {
            echo $sheet;
        }, 'job-order-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        $this->admin($request);
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx', 'extensions:csv,xlsx', 'max:10240'],
        ]);

        $rows = SpreadsheetFile::readRows($request->file('file'));

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'File Excel/CSV kosong atau tidak terbaca.',
            ]);
        }

        $headerAliases = [
            'date' => 'date',
            'tanggal jo' => 'date',
            'so no' => 'so_no',
            'so no.' => 'so_no',
            'so_no' => 'so_no',
            'po no' => 'po_no',
            'po no.' => 'po_no',
            'po_no' => 'po_no',
            'customer no' => 'customer_no',
            'customer_no' => 'customer_no',
            'customer name' => 'customer_name',
            'customer_name' => 'customer_name',
            'pic sales' => 'pic_name',
            'pic_name' => 'pic_name',
            'shipping date' => 'shipping_date',
            'shipping_date' => 'shipping_date',
            'kg' => 'kg',
            'product code' => 'product_code',
            'product_code' => 'product_code',
            'product name' => 'product_name',
            'product_name' => 'product_name',
            'packing' => 'packing',
            'note' => 'note',
        ];
        $headers = array_map(
            fn ($header): ?string => $headerAliases[mb_strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $header)))] ?? null,
            $rows[0]
        );
        $requiredHeaders = ['date', 'so_no', 'po_no', 'customer_name', 'pic_name', 'kg', 'product_name'];

        if (array_diff($requiredHeaders, $headers) !== []) {
            throw ValidationException::withMessages([
                'file' => 'Header wajib: date, so_no, po_no, customer_name, pic_name, kg, dan product_name. Unduh template untuk format lengkap.',
            ]);
        }

        $groups = [];
        $rowCount = 0;

        foreach (array_slice($rows, 1, null, true) as $rowIndex => $cells) {
            $row = [];

            foreach ($headers as $columnIndex => $field) {
                if ($field === null) {
                    continue;
                }

                $value = trim((string) ($cells[$columnIndex] ?? ''));
                $row[$field] = $value === '' ? null : $value;
            }

            if (count(array_filter($row, fn ($value): bool => $value !== null)) === 0) {
                continue;
            }

            $line = $rowIndex + 1;
            foreach (['date', 'shipping_date'] as $dateField) {
                if (isset($row[$dateField]) && is_numeric($row[$dateField]) && $row[$dateField] >= 20000 && $row[$dateField] <= 80000) {
                    $row[$dateField] = SpreadsheetDate::excelToDateTimeObject((float) $row[$dateField])->format('Y-m-d');
                }
            }

            $rowCount++;
            if ($rowCount > 500) {
                throw ValidationException::withMessages([
                    'file' => 'Impor dibatasi maksimal 500 baris detail per file.',
                ]);
            }

            $groupKey = mb_strtolower((string) ($row['so_no'] ?? ''));

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'line' => $line,
                    'order' => [
                        ...array_intersect_key($row, array_flip([
                            'date',
                            'so_no',
                            'po_no',
                            'customer_no',
                            'customer_name',
                            'pic_name',
                            'shipping_date',
                        ])),
                        'inquiry_id' => null,
                        'details' => [],
                    ],
                ];
            } else {
                foreach (
                    [
                        'date',
                        'so_no',
                        'po_no',
                        'customer_no',
                        'customer_name',
                        'pic_name',
                        'shipping_date',
                    ] as $sharedField
                ) {
                    if (($groups[$groupKey]['order'][$sharedField] ?? null) !== ($row[$sharedField] ?? null)) {
                        throw ValidationException::withMessages([
                            'file' => "Baris {$line}: data header pesanan untuk SO {$row['so_no']} harus sama di semua baris detail.",
                        ]);
                    }
                }
            }

            $groups[$groupKey]['order']['details'][] = [
                'inquiry_detail_id' => null,
                'kg' => $row['kg'] ?? null,
                'product_code' => $row['product_code'] ?? null,
                'product_name' => $row['product_name'] ?? null,
                'packing' => $row['packing'] ?? null,
                'note' => $row['note'] ?? null,
            ];
        }

        if ($groups === []) {
            throw ValidationException::withMessages([
                'file' => 'File tidak berisi baris detail Job Order.',
            ]);
        }

        $employeesByName = DB::table('core.employees')
            ->where('is_active', true)
            ->get(['id', 'first_name', 'last_name'])
            ->groupBy(fn ($employee): string => mb_strtolower($this->employeeName($employee) ?? ''));
        $customerCodes = collect($groups)
            ->pluck('order.customer_no')
            ->filter()
            ->map(fn ($code): string => mb_strtolower(trim((string) $code)))
            ->unique()
            ->all();
        $customerNames = collect($groups)
            ->pluck('order.customer_name')
            ->filter()
            ->map(fn ($name): string => mb_strtolower(trim((string) $name)))
            ->unique()
            ->all();
        $customers = CustomerModel::query()
            ->where(function ($query) use ($customerCodes, $customerNames): void {
                if ($customerCodes !== []) {
                    $query->whereIn(DB::raw('LOWER(TRIM(code))'), $customerCodes);
                }

                if ($customerNames !== []) {
                    $method = $customerCodes === [] ? 'whereIn' : 'orWhereIn';
                    $query->{$method}(DB::raw('LOWER(TRIM(name))'), $customerNames);
                }

                if ($customerCodes === [] && $customerNames === []) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->get(['id', 'code', 'name']);
        $customersByCode = $customers
            ->filter(fn (CustomerModel $customer): bool => filled($customer->code))
            ->groupBy(fn (CustomerModel $customer): string => mb_strtolower(trim($customer->code)));
        $customersByName = $customers
            ->filter(fn (CustomerModel $customer): bool => filled($customer->name))
            ->groupBy(fn (CustomerModel $customer): string => mb_strtolower(trim($customer->name)));
        $orders = [];
        $errors = [];
        foreach ($groups as $group) {
            $customerCode = mb_strtolower(trim((string) ($group['order']['customer_no'] ?? '')));
            $customerName = mb_strtolower(trim((string) ($group['order']['customer_name'] ?? '')));
            $matchingCustomers = $customerCode !== ''
                ? $customersByCode->get($customerCode, collect())
                : $customersByName->get($customerName, collect());

            if ($matchingCustomers->count() !== 1) {
                $errors[] = "Baris {$group['line']}: Customer '{$group['order']['customer_name']}' tidak ditemukan atau tidak unik.";

                continue;
            }

            $group['order']['customer_id'] = $matchingCustomers->first()->id;
            $picName = mb_strtolower(trim((string) ($group['order']['pic_name'] ?? '')));
            $matchingEmployees = $employeesByName->get($picName, collect());

            if ($matchingEmployees->count() !== 1) {
                $errors[] = "Baris {$group['line']}: PIC Sales '{$group['order']['pic_name']}' tidak ditemukan atau tidak unik.";

                continue;
            }

            $group['order']['pic_id'] = $matchingEmployees->first()->id;
            $validator = Validator::make($group['order'], $this->validationRules(null, true, true));

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors[] = "Baris {$group['line']} (SO {$group['order']['so_no']}): {$message}";
                }

                continue;
            }

            $orders[] = $validator->validated();
        }

        $soNumbers = array_column($orders, 'so_no');
        $existingSoNumbers = DB::table('sales.job_orders')
            ->whereIn('so_no', $soNumbers)
            ->pluck('so_no')
            ->all();

        foreach ($orders as $order) {
            if (in_array($order['so_no'], $existingSoNumbers, true)) {
                $errors[] = "Baris SO {$order['so_no']}: SO No. sudah digunakan.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        DB::transaction(function () use ($orders, $request): void {
            foreach ($orders as $order) {
                $id = DB::table('sales.job_orders')->insertGetId([
                    'inquiry_id' => null,
                    'date' => $order['date'],
                    'so_no' => $order['so_no'],
                    'po_no' => $order['po_no'],
                    'customer_id' => $order['customer_id'],
                    'pic_id' => $order['pic_id'],
                    'shipping_date' => $order['shipping_date'] ?? null,
                    'status' => 'pending',
                    'created_by' => $request->user()->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'id');
                $this->saveDetails($id, $order['details']);
            }
        });

        return response()->json([
            'created_count' => count($orders),
            'message' => count($orders).' Job Order berhasil diimpor.',
        ], 201);
    }

    private function validationRules(?int $id, bool $isDirect, bool $requireCustomerSelection = false): array
    {
        return [
            'inquiry_id' => [
                ...($id ? ['sometimes'] : []),
                'nullable',
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
            'customer_id' => [
                $requireCustomerSelection ? 'required' : 'nullable',
                'integer',
                Rule::exists(DB::connection()->getName().'.core.customers', 'id'),
            ],
            'pic_id' => [
                $isDirect ? 'required' : 'nullable',
                'integer',
                Rule::exists(DB::connection()->getName().'.core.employees', 'id'),
            ],
            'shipping_date' => ['nullable', 'date_format:Y-m-d'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.inquiry_detail_id' => ['nullable', 'integer'],
            'details.*.kg' => ['required', 'numeric', 'min:0'],
            'details.*.product_code' => ['nullable', 'string', 'max:100'],
            'details.*.product_name' => ['required', 'string', 'max:255'],
            'details.*.packing' => ['nullable', 'string', 'max:255'],
            'details.*.note' => ['nullable', 'string'],
        ];
    }

    private function validated(Request $request, ?int $id = null, bool $isDirect = false, bool $requireCustomerSelection = false): array
    {
        return $request->validate($this->validationRules($id, $isDirect, $requireCustomerSelection));
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
        $isDirect = ! $request->filled('inquiry_id');
        $data = $this->validated($request, isDirect: $isDirect, requireCustomerSelection: $isDirect);
        $inquiryId = $data['inquiry_id'] ?? null;

        if ($inquiryId !== null) {
            abort_unless($this->visibleInquiries($request)->where('i.id', $inquiryId)->exists(), 403);
        }

        $id = DB::transaction(function () use ($request, $data) {
            $inquiry = isset($data['inquiry_id'])
                ? DB::table('sales.inquiries')->where('id', $data['inquiry_id'])->lockForUpdate()->first()
                : null;

            if (isset($data['inquiry_id'])) {
                abort_unless($inquiry, 404);
                abort_if(DB::table('sales.job_orders')->where('inquiry_id', $inquiry->id)->exists(), 409, 'JO sudah ada.');
            }

            $id = DB::table('sales.job_orders')->insertGetId([
                'inquiry_id' => $inquiry?->id,
                'date' => $data['date'],
                'so_no' => $data['so_no'],
                'po_no' => $data['po_no'],
                'customer_id' => $inquiry !== null ? $inquiry->customer_id : $data['customer_id'],
                'pic_id' => $inquiry?->pic ?? ($data['pic_id'] ?? $request->user()->id),
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
        $jo = $this->accessibleJo($request, $id);
        $isDirect = $jo->inquiry_id === null;
        $data = $this->validated($request, $id, $isDirect, $isDirect);
        DB::transaction(function () use ($id, $data, $request, $jo) {
            $jo = DB::table('sales.job_orders')->where('id', $id)->lockForUpdate()->first();
            abort_if($jo->status === 'approved' || $jo->status === 'checked', 409, 'JO yang sudah approved tidak dapat diubah.');
            DB::table('sales.job_orders')->where('id', $id)->update([
                'date' => $data['date'],
                'so_no' => $data['so_no'],
                'po_no' => $data['po_no'],
                'customer_id' => $jo->inquiry_id === null ? $data['customer_id'] : $jo->customer_id,
                'pic_id' => $jo->inquiry_id === null ? $data['pic_id'] : $jo->pic_id,
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
            ->whereIn('id', $this->visibleJobOrders($request)->select('jo.id'))
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
            ->whereIn('id', $this->visibleJobOrders($request)->select('jo.id'))
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
        abort_unless(
            array_intersect($this->roleIds($request), [1, 2, 3, 4, 5]) !== [],
            403
        );
        $accessibleJo = $this->accessibleJo($request, $id);
        $jo = JobOrderModel::query()
            ->with(['customer', 'picEmployee', 'createdByEmployee', 'checkedByEmployee', 'approvedByEmployee'])
            ->findOrFail($accessibleJo->id);
        $details = DB::table('sales.job_order_details')->where('job_order_id', $id)->orderBy('sort_order')->get();
        $signerIds = collect([$jo->created_by, $jo->checked_by, $jo->approved_by])
            ->filter()
            ->unique()
            ->values();
        $signatureNames = [
            'created_by' => $jo->createdByEmployee?->full_name,
            'checked_by' => $jo->checkedByEmployee?->full_name,
            'approved_by' => $jo->approvedByEmployee?->full_name,
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
