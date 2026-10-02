<?php

namespace App\Models\Core;

use App\Models\Sales\CompetitorModel;
use App\Models\Sales\CompetitorProductModel;
use App\Models\Sales\InquiryDetailModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductModel extends Model
{
    use SoftDeletes;

    protected $table = 'core.products';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'code',
        'std_price',
        'grade_id',
    ];

    protected $casts = [
        'std_price' => 'decimal:2',
    ];

    public function material(): MorphTo
    {
        return $this->morphTo();
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(GradeModel::class, 'grade_id');
    }

    public function inquiryDetails(): HasMany
    {
        return $this->hasMany(
            InquiryDetailModel::class,
            'product_id'
        );
    }

    public function competitorProducts(): HasMany
    {
        return $this->hasMany(
            CompetitorProductModel::class,
            'product_id',
            'id'
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

    public function materials(): HasMany
    {
        return $this->hasMany(
            ProductMaterialModel::class,
            'product_id'
        );
    }
}
