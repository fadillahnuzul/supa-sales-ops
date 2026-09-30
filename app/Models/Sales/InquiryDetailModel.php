<?php

namespace App\Models\Sales;

use App\Models\Core\GradeModel;
use App\Models\Core\ProductModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InquiryDetailModel extends Model
{
    use SoftDeletes;

    protected $table = 'sales.inquiry_details';

    protected $fillable = [
        'inquiry_id',
        'product_id',
        'grade_id',
        'qty',
        'product_std_price',
        'alternative_price',
        'source_ap',
        'date_ap',
        'reference_price',
        'last_order_date',
        'last_order_price',
        'last_quotation_date',
        'last_quotation_price',
        'recommended_price',
        'approved_price',
        'approved_date',
        'offer_1_price',
        'offer_2_price',
        'offer_3_price',
        'final_price',
        'note',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'product_std_price' => 'decimal:2',
        'alternative_price' => 'decimal:2',
        'reference_price' => 'decimal:2',
        'last_order_price' => 'decimal:2',
        'last_quotation_price' => 'decimal:2',
        'recommended_price' => 'decimal:2',
        'approved_price' => 'decimal:2',
        'offer_1_price' => 'decimal:2',
        'offer_2_price' => 'decimal:2',
        'offer_3_price' => 'decimal:2',
        'final_price' => 'decimal:2',

        'date_ap' => 'date',
        'last_order_date' => 'date',
        'last_quotation_date' => 'date',
        'approved_date' => 'date',
    ];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(InquiryModel::class, 'inquiry_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(GradeModel::class, 'grade_id');
    }

    public function sourceAp(): BelongsTo
    {
        return $this->belongsTo(CompetitorModel::class, 'source_ap');
    }
}
