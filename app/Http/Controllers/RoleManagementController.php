<?php

namespace App\Http\Controllers;

use App\Models\Core\EmployeeModel;
use App\Models\Core\SegmentationModel;
use App\Models\Sales\RoleModel;
use App\Models\Sales\UserRoleModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class RoleManagementController extends Controller
{
    public function index()
    {
        $segmentation = SegmentationModel::orderBy('name')->get();
        $employeeList = EmployeeModel::orderBy('first_name')->get();
        $roles = RoleModel::orderBy('name')->get();

        $assignments = DB::table('sales.user_role as ur')
            ->join('core.employees as e', 'e.id', '=', 'ur.employee_id')
            ->leftJoin('sales.roles as r', 'r.id', '=', 'ur.role_id')
            ->select([
                'ur.employee_id',
                'ur.role_id',
                'ur.sales_code',
                'r.name as role_name',
                'e.first_name',
                'e.last_name',
                'e.username',
            ])
            ->orderBy('e.id')
            ->orderBy('r.name')
            ->get();

        $employees = EmployeeModel::query()
            ->with(['segmentation', 'segmentation.segmentation'])
            ->whereIn('id', $assignments->pluck('employee_id')->unique())
            ->get()
            ->keyBy('id');

        $users = $assignments
            ->groupBy('employee_id')
            ->map(function ($employeeAssignments, $employeeId) use ($employees): array {
                $employee = $employees->get((int) $employeeId);
                $roleNames = $employeeAssignments
                    ->pluck('role_name')
                    ->filter()
                    ->values()
                    ->all();
                $segmentationIds = $employee?->segmentation
                    ->pluck('segmentation_id')
                    ->map(fn ($id): int => (int) $id)
                    ->values()
                    ->all() ?? [];
                $segmentationNames = $employee?->segmentation
                    ->pluck('segmentation.name')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all() ?? [];
                $salesRole = $employeeAssignments
                    ->first(fn ($assignment): bool => (int) $assignment->role_id === 2);

                return [
                    'id' => (int) $employeeId,
                    'employee_id' => (int) $employeeId,
                    'name' => trim(($employee?->first_name ?? '').' '.($employee?->last_name ?? '')),
                    'username' => $employee?->username,
                    'role_ids' => $employeeAssignments
                        ->pluck('role_id')
                        ->map(fn ($roleId): int => (int) $roleId)
                        ->values()
                        ->all(),
                    'role_names' => $roleNames,
                    'role_name' => implode(', ', $roleNames),
                    'sales_code' => $salesRole?->sales_code,
                    'segmentation_ids' => $segmentationIds,
                    'segmentation_names' => $segmentationNames,
                ];
            })
            ->values();

        return Inertia::render('InquiryRoleManagement', [
            'employeeList' => $employeeList,
            'segmentation' => $segmentation,
            'roles' => $roles,
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        if (DB::table('sales.user_role')
            ->where('employee_id', $data['employee_id'])
            ->exists()) {
            throw ValidationException::withMessages([
                'employee_id' => 'Employee sudah memiliki user access.',
            ]);
        }

        DB::transaction(function () use ($data): void {
            $this->syncRoles($data['employee_id'], $data['role_ids'], $data['sales_code']);
            $this->syncSegmentation(
                $data['employee_id'],
                $data['segmentation_ids']
            );
        });

        return redirect()
            ->route('role-management')
            ->with('success', 'User access berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $employee = EmployeeModel::with('segmentation')->findOrFail($id);

        return response()->json([
            'id' => $employee->id,
            'employee_id' => (int) $employee->id,
            'name' => $employee->full_name,
            'username' => $employee->username,
            'role_ids' => $employee->roleIds(),
            'sales_code' => DB::table('sales.user_role')
                ->where('employee_id', $id)
                ->where('role_id', 2)
                ->value('sales_code'),
            'segmentation_ids' => $employee->segmentation
                ->pluck('segmentation_id')
                ->map(fn ($segmentationId): int => (int) $segmentationId)
                ->values()
                ->all(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $data = $this->validateData($request, $id);

        if ((int) $data['employee_id'] !== $id) {
            throw ValidationException::withMessages([
                'employee_id' => 'Employee tidak dapat diganti.',
            ]);
        }

        $salesCode = $request->exists('sales_code')
            ? ($data['sales_code'] ?? null)
            : DB::table('sales.user_role')
                ->where('employee_id', $id)
                ->where('role_id', 2)
                ->value('sales_code');

        DB::transaction(function () use ($data, $id, $salesCode): void {
            $this->syncRoles($id, $data['role_ids'], $salesCode);
            $this->syncSegmentation(
                $id,
                $data['segmentation_ids']
            );
        });

        return redirect()
            ->route('role-management')
            ->with('success', 'User access berhasil diperbarui.');
    }

    /**
     * @return array{
     *     employee_id: int,
     *     role_ids: list<int>,
     *     sales_code: ?string,
     *     segmentation_ids: list<int>
     * }
     */
    private function validateData(Request $request, ?int $ignoreEmployeeId = null): array
    {
        $salesCodeUnique = Rule::unique(UserRoleModel::class, 'sales_code')
            ->where(fn ($query) => $query->where('role_id', 2));

        if ($ignoreEmployeeId !== null) {
            $salesCodeUnique->ignore($ignoreEmployeeId, 'employee_id');
        }

        $data = $request->validate([
            'employee_id' => [
                'required',
                'integer',
                Rule::exists(EmployeeModel::class, 'id'),
            ],
            'role_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'role_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists(RoleModel::class, 'id'),
            ],
            'sales_code' => [
                'sometimes',
                'nullable',
                'string',
                'size:2',
                'regex:/^\d{2}$/D',
                $salesCodeUnique,
            ],
            'segmentation_ids' => [
                'present',
                'array',
            ],
            'segmentation_ids.*' => [
                'integer',
                'distinct',
                Rule::exists(SegmentationModel::class, 'id'),
            ],
        ], [
            'employee_id.required' => 'Employee wajib dipilih.',
            'role_ids.required' => 'Pilih minimal satu role.',
            'role_ids.min' => 'Pilih minimal satu role.',
            'role_ids.*.exists' => 'Role tidak valid.',
            'sales_code.size' => 'Kode Sales harus terdiri dari dua digit.',
            'sales_code.regex' => 'Kode Sales harus terdiri dari dua digit.',
            'sales_code.unique' => 'Kode Sales sudah digunakan employee lain.',
            'segmentation_ids.*.exists' => 'Segmentation tidak valid.',
        ]);

        return [
            'employee_id' => (int) $data['employee_id'],
            'role_ids' => array_map('intval', $data['role_ids']),
            'sales_code' => $data['sales_code'] ?? null,
            'segmentation_ids' => array_map('intval', $data['segmentation_ids']),
        ];
    }

    /**
     * @param  list<int>  $roleIds
     */
    private function syncRoles(int $employeeId, array $roleIds, ?string $salesCode): void
    {
        DB::table('sales.user_role')
            ->where('employee_id', $employeeId)
            ->delete();

        DB::table('sales.user_role')->insert(
            array_map(
                fn (int $roleId): array => [
                    'employee_id' => $employeeId,
                    'role_id' => $roleId,
                    'sales_code' => $roleId === 2 ? $salesCode : null,
                ],
                $roleIds
            )
        );
    }

    /**
     * @param  list<int>  $segmentationIds
     */
    private function syncSegmentation(int $employeeId, array $segmentationIds): void
    {
        DB::table('core.employee_segmentations')
            ->where('employee_id', $employeeId)
            ->delete();

        foreach ($segmentationIds as $segmentationId) {
            DB::table('core.employee_segmentations')->insert([
                'employee_id' => $employeeId,
                'segmentation_id' => $segmentationId,
            ]);
        }
    }
}
