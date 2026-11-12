<?php

namespace App\Models\Sales;

use App\Models\Core\CustomerModel;
use App\Models\Core\EmployeeModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOrderModel extends Model
{
    protected $table = 'sales.job_orders';

    protected $appends = [
        'customer_no',
        'customer_name',
        'customer_address',
        'pic_name',
    ];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(InquiryModel::class, 'inquiry_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(JobOrderDetailModel::class, 'job_order_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerModel::class, 'customer_id');
    }

    public function picEmployee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'pic_id')->withTrashed();
    }

    public function createdByEmployee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'created_by')->withTrashed();
    }

    public function checkedByEmployee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'checked_by')->withTrashed();
    }

    public function approvedByEmployee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'approved_by')->withTrashed();
    }

    public function getCustomerNoAttribute(): ?string
    {
        return $this->customer?->code;
    }

    public function getCustomerNameAttribute(): ?string
    {
        return $this->customer?->name;
    }

    public function getCustomerAddressAttribute(): ?string
    {
        return $this->customer?->address;
    }

    public function getPicNameAttribute(): ?string
    {
        return $this->picEmployee?->full_name;
    }
}
