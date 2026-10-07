<?php

namespace App\Http\Controllers;

use App\Models\Sales\SalesSignatureModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SalesSignatureController extends Controller
{
    public function index()
    {
        return response()->json(
            SalesSignatureModel::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
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
}
