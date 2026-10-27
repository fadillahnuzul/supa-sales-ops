<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSegmentationModel extends Model
{
    protected $table = 'core.employee_segmentations';
    public $timestamps = false;
    public $incrementing = false;

    public function segmentation() : BelongsTo {
        return $this->belongsTo(SegmentationModel::class, 'segmentation_id', 'id');
    }
}
