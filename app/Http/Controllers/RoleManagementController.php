<?php

namespace App\Http\Controllers;

use App\Models\Core\EmployeeModel;
use App\Models\Core\EmployeeSegmentationModel;
use App\Models\Core\SegmentationModel;
use App\Models\Sales\RoleModel;
use App\Models\Sales\UserRoleModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RoleManagementController extends Controller
{
    public function index()
    {
        $segmentation = SegmentationModel::orderBy('name')->get();
        $employeeList = EmployeeModel::orderBy('first_name')->get();
        $roles = RoleModel::orderBy('name')->get();

        $users = UserRoleModel::get()->map(function ($user) {
            $employee = EmployeeModel::with('segmentation','segmentation.segmentation', 'role')->find($user->employee_id);

            return [
                'id' => $user->id,
                'employee_id' => (int) $user->employee_id,
                'name' => trim(
                    ($employee?->first_name ?? '') . ' ' .
                        ($employee?->last_name ?? '')
                ),
                'username' => $employee?->username,
                'role_id' => $user->role_id
                    ? (int) $user->role_id
                    : null,
                'role_name' => $employee->role->role->name,
                'segmentation_ids' => ($employee->segmentation) ? $employee->segmentation->pluck('segmentation_id') : [],
                'segmentation_names' => ($employee->segmentation) ? $employee->segmentation->pluck('segmentation.name') : [],
            ];
        });

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

        DB::transaction(function () use ($data) {

            $userRole = new UserRoleModel();
            $userRole->employee_id = $data['employee_id'];
            $userRole->role_id = $data['role_id'];
            $userRole->save();

            $this->syncSegmentation(
                $data['employee_id'],
                $data['segmentation_ids'] ?? []
            );
        });

        return redirect()
            ->route('role-management')
            ->with('success', 'User access berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $user = UserRoleModel::findOrFail($id);

        $employee = EmployeeModel::find($user->employee_id);

        $segmentationIds = EmployeeSegmentationModel::where(
            'employee_id',
            $user->employee_id
        )->pluck('segmentation_id')->map(
            fn($id) => (int) $id
        )->values()->all();

        return response()->json([
            'id' => $user->id,
            'employee_id' => (int) $user->employee_id,
            'name' => trim(
                ($employee?->first_name ?? '') . ' ' .
                    ($employee?->last_name ?? '')
            ),
            'username' => $employee?->username,
            'role_id' => $user->role_id
                ? (int) $user->role_id
                : null,
            'segmentation_ids' => $segmentationIds,
        ]);
    }

    /**
     * UPDATE - Perbarui user access
     */
    public function update(Request $request, $id)
    {
        $data = $this->validateData($request);
        $userRole = UserRoleModel::updateOrCreate(
            ['employee_id' => $id],
            ['role_id' => $data['role_id']]
        );

        DB::transaction(function () use ($data, $userRole) {

            $oldEmployeeId = $userRole->employee_id;
            // $userRole->employee_id = $data['employee_id'];
            // $userRole->role_id = $data['role_id'];
            // $userRole->save();

            if ((int) $oldEmployeeId !== (int) $data['employee_id']) {
                EmployeeSegmentationModel::where(
                    'employee_id',
                    $oldEmployeeId
                )->delete();
            }

            $this->syncSegmentation(
                $data['employee_id'],
                $data['segmentation_ids'] ?? []
            );
        });

        return redirect()
            ->route('role-management')
            ->with('success', 'User access berhasil diperbarui.');
    }

    /**
     * VALIDATION
     */
    private function validateData(Request $request, $ignoreId = null)
    {
        $userRoleTable = (new UserRoleModel())->getTable();
        $employeeTable = (new EmployeeModel())->getTable();
        $roleTable = (new RoleModel())->getTable();
        $segmentationTable = (new SegmentationModel())->getTable();

        $uniqueEmployee = Rule::unique(
            UserRoleModel::class,
            'employee_id'
        );

        if ($ignoreId !== null) {
            $uniqueEmployee->ignore($ignoreId);
        }

        return $request->validate([
            'employee_id' => [
                'required',
                'integer',
                Rule::exists(EmployeeModel::class, 'id'),
                // $uniqueEmployee,
            ],
            'role_id' => [
                'required',
                'integer',
                Rule::exists(RoleModel::class, 'id'),
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
            // 'employee_id.unique' => 'Employee sudah memiliki user access.',
            'role_id.required' => 'Role wajib dipilih.',
            'segmentation_ids.*.exists' => 'Segmentation tidak valid.',
        ]);
    }

    private function syncSegmentation(
        int $employeeId,
        array $segmentationIds
    ) {
        EmployeeSegmentationModel::where(
            'employee_id',
            $employeeId
        )->delete();

        // Simpan pilihan segmentation terbaru.
        foreach ($segmentationIds as $segmentationId) {
            $employeeSegmentation = new EmployeeSegmentationModel();
            $employeeSegmentation->employee_id = $employeeId;
            $employeeSegmentation->segmentation_id = $segmentationId;
            $employeeSegmentation->save();
        }
    }
}
