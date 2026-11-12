<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobOrderDetailModel extends Model
{
    protected $table = 'sales.job_order_details';

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrderModel::class, 'job_order_id');
    }
}
