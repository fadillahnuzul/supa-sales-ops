<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;

class SalesSignatureModel extends Model
{
    protected $table = 'sales.sales_signatures';

    protected $fillable = [
        'user_id',
        'name',
        'division',
        'position',
        'signature_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
