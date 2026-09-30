<?php

namespace App\Models\Core;

use App\Models\Sales\CompetitorModel;
use App\Models\Sales\InquiryDetailModel;
use App\Models\Sales\ProductCompetitorModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductModel extends Model
{
    use SoftDeletes;

    protected $table = 'core.products';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'name_indonesian',
        'code',
        'std_price',
    ];

    protected $casts = [
        'std_price' => 'decimal:2',
    ];

    public function inquiryDetails(): HasMany
    {
        return $this->hasMany(
            InquiryDetailModel::class,
            'product_id'
        );
    }

    public function competitorPrices(): HasMany
    {
        return $this->hasMany(
            ProductCompetitorModel::class,
            'product_id'
        );
    }

    public function competitors(): BelongsToMany
    {
        return $this->belongsToMany(
            CompetitorModel::class,
            'sales.product_competitors',
            'product_id',
            'competitor_id'
        )
        ->withPivot([
            'id',
            'price',
            'date',
            'note',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);
    }
}
