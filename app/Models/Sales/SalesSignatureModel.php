<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesSignatureModel extends Model
{
    use SoftDeletes;
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
