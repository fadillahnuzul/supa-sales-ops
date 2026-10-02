<?php

namespace App\Models\Sales;

use App\Models\Core\ProductModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorProductModel extends Model
{
    protected $table = 'sales.competitor_products';

    protected $fillable = [
        'competitor_id',
        'product_id',
        'price',
        'note',
        'date',
    ];

    protected $casts = [
        'competitor_id' => 'integer',
        'product_id' => 'integer',
        'price' => 'decimal:2',
        'date' => 'date:Y-m-d',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(
            CompetitorModel::class,
            'competitor_id'
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            ProductModel::class,
            'product_id'
        );
    }
}
