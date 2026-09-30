<?php

namespace App\Models\Sales;

use App\Models\Core\CustomerModel;
use App\Models\Core\EmployeeModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class InquiryModel extends Model
{
    use SoftDeletes;

    protected $table = 'sales.inquiries';

    protected $fillable = [
        'code',
        'date',
        'etd',
        'pic',
        'customer_id',
        'shipping_rate',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
        'etd' => 'date',
        'shipping_rate' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerModel::class, 'customer_id');
    }

    public function picEmployee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'pic');
    }

    public function details(): HasMany
    {
        return $this->hasMany(InquiryDetailModel::class, 'inquiry_id');
    }

}
