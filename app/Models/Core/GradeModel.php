<?php

namespace App\Models\Core;

use App\Models\Sales\InquiryDetailModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeModel extends Model
{
    protected $table = 'core.grades';

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public function inquiryDetails(): HasMany
    {
        return $this->hasMany(
            InquiryDetailModel::class,
            'grade_id'
        );
    }
}
