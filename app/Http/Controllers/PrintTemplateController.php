<?php

namespace App\Http\Controllers;

use App\Models\Sales\PrintTemplateModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PrintTemplateController extends Controller
{
    public function index()
    {
        return response()->json(
            PrintTemplateModel::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
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

            'file' => [
                'required',
                'file',
                'mimes:docx',
                'max:10240',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Store File
        |--------------------------------------------------------------------------
        */

        $filePath = $request
            ->file('file')
            ->store(
                'inquiry-templates/quotation'
            );

        /*
        |--------------------------------------------------------------------------
        | Code
        |--------------------------------------------------------------------------
        */

        $code =
            strtoupper(
                Str::slug(
                    $request->name,
                    '_'
                )
            );

        $baseCode = $code;
        $counter = 1;

        while (
            PrintTemplateModel::where(
                'code',
                $code
            )->exists()
        ) {
            $code =
                $baseCode.
                '_'.
                $counter;

            $counter++;
        }

        /*
        |--------------------------------------------------------------------------
        | Default
        |--------------------------------------------------------------------------
        */

        if ($request->boolean('is_default')) {

            PrintTemplateModel::query()
                ->where(
                    'template_type',
                    'QUOTATION'
                )
                ->update([
                    'is_default' => false,
                ]);
        }

        $template =
            PrintTemplateModel::create([
                'name' => $request->name,

                'code' => $code,

                'division' => $request->division,

                'template_type' => 'QUOTATION',

                'file_path' => $filePath,

                'is_default' => $request
                    ->boolean(
                        'is_default'
                    ),

                'is_active' => true,
            ]);

        return response()->json(
            $template,
            201
        );
    }

    public function destroy(
        PrintTemplateModel $printTemplate
    ) {
        if (
            Storage::exists(
                $printTemplate->file_path
            )
        ) {
            Storage::delete(
                $printTemplate->file_path
            );
        }

        $printTemplate->delete();

        return response()->json([
            'message' => 'Template berhasil dihapus.',
        ]);
    }
}
