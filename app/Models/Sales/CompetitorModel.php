<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function products(): HasMany
    {
        return $this->hasMany(
            CompetitorProductModel::class,
            'competitor_id',
            'id'
        );
    }

    public function inquiryDetails(): HasMany
    {
        return $this->hasMany(
            InquiryDetailModel::class,
            'source_ap'
        );
    }
}
