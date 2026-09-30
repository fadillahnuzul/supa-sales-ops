<?php

namespace App\Models\Sales;

use App\Models\Core\ProductModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CompetitorModel extends Model
{
    use SoftDeletes;

    protected $table = 'sales.competitors';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'divisi',
        'note',
    ];

    public function productPrices(): HasMany
    {
        return $this->hasMany(
            ProductCompetitorModel::class,
            'competitor_id'
        );
    }

    public function inquiryDetails(): HasMany
    {
        return $this->hasMany(
            InquiryDetailModel::class,
            'source_ap'
        );
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductModel::class,
            'sales.product_competitors',
            'competitor_id',
            'product_id'
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
