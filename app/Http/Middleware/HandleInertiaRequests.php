<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
{
    $employee = $request->user();

    $permissions = $employee?->is_active
    ? [
        'app.view',
        'assets.view',
        'assets.manage',
        'inquiries.write',
        'print.export',
    ]
    : [];

    // if ($employee?->canManagePrintAssets()) {
    //     $permissions[] = 'assets.manage';
    // }

    return [
        ...parent::share($request),

        'auth' => [
            'user' => $employee
                ? [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'username' => $employee->username,
                    'email' => $employee->email,
                    'photo' => $employee->profile_photo_path
                        ? asset(
                            'storage/' . $employee->profile_photo_path
                        )
                        : null,
                ]
                : null,

            'permissions' => $permissions,
        ],
    ];
}
}
