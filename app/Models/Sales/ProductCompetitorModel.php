<?php

namespace App\Models\Sales;

use App\Models\Core\ProductModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCompetitorModel extends Model
{
    use SoftDeletes;

    protected $table = 'sales.product_competitors';

    protected $fillable = [
        'competitor_id',
        'product_id',
        'price',
        'date',
        'note',
    ];
 
    protected $casts = [
        'price' => 'decimal:2',
        'date' => 'date',
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
