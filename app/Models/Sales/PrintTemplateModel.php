<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

class PrintTemplateModel extends Model
{
    protected $table = 'sales.print_templates';

    protected $fillable = [
        'name',
        'code',
        'division',
        'template_type',
        'file_path',
        'is_default',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];
}
