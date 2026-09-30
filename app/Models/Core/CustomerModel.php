<?php

namespace App\Models\Core;

use App\Models\Sales\InquiryModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerModel extends Model
{
    protected $table = 'core.customers';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'segmentation_id',
        'level',
        'divisi',
        'sterilization',
    ];

    public function segmentation(): BelongsTo
    {
        return $this->belongsTo(
            SegmentationModel::class,
            'segmentation_id'
        );
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(
            InquiryModel::class,
            'customer_id'
        );
    }
}
