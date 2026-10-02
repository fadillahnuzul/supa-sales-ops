<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductMaterialModel extends Model
{
    protected $table =
        'core.product_materials';

    protected $fillable = [
        'product_id',
        'material_id',
        'material_type',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            ProductModel::class,
            'product_id'
        );
    }

    public function material(): MorphTo
    {
        return $this->morphTo(
            'material'
        );
    }
}
