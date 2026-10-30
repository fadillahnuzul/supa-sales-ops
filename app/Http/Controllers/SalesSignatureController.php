<?php

namespace App\Http\Controllers;

use App\Models\Core\EmployeeModel;
use App\Models\Sales\SalesSignatureModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SalesSignatureController extends Controller
{
    public function settings(Request $request): Response
    {
        $employee = $this->employeeWithRegisteredRole($request);
        $signature = SalesSignatureModel::query()
            ->where('user_id', $employee->id)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        return Inertia::render('SignatureSettings', [
            'signature' => $signature ? [
                'name' => $signature->name,
                'position' => $signature->position,
                'imageUrl' => route('signature-settings.file', $signature->id),
            ] : null,
        ]);
    }

    public function file(Request $request, int $id): BinaryFileResponse
    {
        $this->employeeWithRegisteredRole($request);
        $signature = SalesSignatureModel::query()
            ->where('is_active', true)
            ->findOrFail($id);
        $path = $signature->signature_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $absolutePath = Storage::disk('local')->path($path);
        abort_unless(File::isFile($absolutePath), 404, 'File tidak ditemukan.');

        $mime = File::mimeType($absolutePath);
        abort_unless(
            in_array($mime, ['image/png', 'image/jpeg', 'image/webp', 'image/gif'], true),
            415,
            'Format gambar tanda tangan tidak didukung.'
        );

        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function storeForCurrentUser(Request $request): RedirectResponse
    {
        $employee = $this->employeeWithRegisteredRole($request);
        $data = $request->validate([
            'signature' => [
                'required',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:2048',
            ],
        ]);
        $employee->loadMissing('role.role');

        $path = $data['signature']->store('private/sales-signatures', 'local');
        abort_if($path === false, 500, 'Gagal menyimpan file tanda tangan.');

        DB::transaction(function () use ($employee, $path): void {
            SalesSignatureModel::query()
                ->where('user_id', $employee->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            SalesSignatureModel::create([
                'user_id' => $employee->id,
                'name' => $employee->full_name,
                'position' => $employee->role?->role?->name,
                'signature_path' => $path,
                'is_active' => true,
            ]);
        });

        return redirect()
            ->route('signature-settings')
            ->with('success', 'Tanda tangan berhasil diunggah.');
    }

    public function index()
    {
        $latestSignatureIds = DB::table('sales.sales_signatures')
            ->select('user_id')
            ->selectRaw('MAX(id) as id')
            ->where('is_active', true)
            ->whereNotNull('user_id')
            ->groupBy('user_id');

        $employees = DB::table('core.employees as e')
            ->leftJoin('sales.user_role as ur', 'ur.employee_id', '=', 'e.id')
            ->leftJoin('sales.roles as r', 'r.id', '=', 'ur.role_id')
            ->leftJoinSub($latestSignatureIds, 'latest_signature', function ($join): void {
                $join->on('latest_signature.user_id', '=', 'e.id');
            })
            ->leftJoin('sales.sales_signatures as signature', 'signature.id', '=', 'latest_signature.id')
            ->whereNull('e.deleted_at')
            ->where(function ($query): void {
                $query->whereIn('ur.role_id', [2, 3, 4])
                    ->orWhereExists(function ($inquiries): void {
                        $inquiries->selectRaw('1')
                            ->from('sales.inquiries as i')
                            ->whereColumn('i.pic', 'e.id')
                            ->whereNull('i.deleted_at');
                    });
            })
            ->select([
                'signature.id',
                'e.id as user_id',
                DB::raw("COALESCE(NULLIF(signature.name, ''), TRIM(COALESCE(e.first_name, '') || ' ' || COALESCE(e.last_name, ''))) as name"),
                'signature.division',
                DB::raw('COALESCE(signature.position, r.name) as position'),
            ])
            ->orderBy('name')
            ->get()
            ->map(static fn (object $signature): array => [
                'id' => $signature->id !== null ? (int) $signature->id : null,
                'user_id' => (int) $signature->user_id,
                'name' => $signature->name,
                'division' => $signature->division,
                'position' => $signature->position,
            ]);

        $unassignedSignatures = SalesSignatureModel::query()
            ->where('is_active', true)
            ->whereNull('user_id')
            ->get(['id', 'user_id', 'name', 'division', 'position'])
            ->map(static fn (SalesSignatureModel $signature): array => [
                'id' => (int) $signature->id,
                'user_id' => null,
                'name' => $signature->name,
                'division' => $signature->division,
                'position' => $signature->position,
            ]);

        return response()->json(
            $employees->concat($unassignedSignatures)
                ->sortBy('name')
                ->values()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => [
                'nullable',
                'integer',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'division' => [
                'nullable',
                'string',
                'max:50',
            ],

            'position' => [
                'nullable',
                'string',
                'max:150',
            ],

            'signature' => [
                'required',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:2048',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Private storage
        |--------------------------------------------------------------------------
        */

        $path = $request
            ->file('signature')
            ->store(
                'private/sales-signatures'
            );

        /*
        |--------------------------------------------------------------------------
        | Disable old signature
        |--------------------------------------------------------------------------
        */

        if ($request->user_id) {

            SalesSignatureModel::query()
                ->where(
                    'user_id',
                    $request->user_id
                )
                ->update([
                    'is_active' => false,
                ]);
        }

        $signature =
            SalesSignatureModel::create([
                'user_id' => $request->user_id,

                'name' => $request->name,

                'division' => $request->division,

                'position' => $request->position,

                'signature_path' => $path,

                'is_active' => true,
            ]);

        return response()->json(
            $signature,
            201
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $signature = SalesSignatureModel::query()
            ->where('is_active', true)
            ->findOrFail($id);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'division' => [
                'nullable',
                'string',
                'max:50',
            ],
            'position' => [
                'nullable',
                'string',
                'max:150',
            ],
            'signature' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:2048',
            ],
        ]);

        $oldSignaturePath = $signature->signature_path;
        $newSignaturePath = null;

        if ($request->hasFile('signature')) {
            $newSignaturePath = $request->file('signature')
                ->store('private/sales-signatures', 'local');
            abort_if($newSignaturePath === false, 500, 'Gagal menyimpan file tanda tangan.');
        }

        try {
            DB::transaction(function () use ($data, $newSignaturePath, $signature): void {
                $signature->fill([
                    'name' => $data['name'],
                    'division' => $data['division'] ?? null,
                    'position' => $data['position'] ?? null,
                ]);

                if ($newSignaturePath !== null) {
                    $signature->signature_path = $newSignaturePath;
                }

                $signature->save();
            });
        } catch (Throwable $exception) {
            if ($newSignaturePath !== null) {
                Storage::disk('local')->delete($newSignaturePath);
            }

            throw $exception;
        }

        if ($newSignaturePath !== null && $oldSignaturePath !== $newSignaturePath) {
            Storage::disk('local')->delete($oldSignaturePath);
        }

        return response()->json([
            'id' => (int) $signature->id,
            'user_id' => $signature->user_id !== null
                ? (int) $signature->user_id
                : null,
            'name' => $signature->name,
            'division' => $signature->division,
            'position' => $signature->position,
        ]);
    }

    private function employeeWithRegisteredRole(Request $request): EmployeeModel
    {
        $employee = $request->user();

        abort_unless(
            $employee instanceof EmployeeModel && $employee->role()->exists(),
            403
        );

        return $employee;
    }
}
