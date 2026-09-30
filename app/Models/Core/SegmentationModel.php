<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SegmentationModel extends Model
{
    protected $table = 'core.segmentations';

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(
            CustomerModel::class,
            'segmentation_id'
        );
    }
}
