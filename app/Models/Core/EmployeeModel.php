<?php

namespace App\Models\Core;

use App\Models\Sales\InquiryModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeModel extends Model
{
    use SoftDeletes;

    protected $table = 'core.employees';

    protected $fillable = [
        'id_karyawan',
        'first_name',
        'last_name',
        'email',
        'no_telepon',
        'no_telepon_alt',
        'birth_place',
        'birth_date',
        'marital_status',
        'nik',
        'address_ktp',
        'address_residence',
        'hire_date',
        'is_active',
        'password',
        'profile_photo_path',
        'username',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function inquiries(): HasMany
    {
        return $this->hasMany(
            InquiryModel::class,
            'pic'
        );
    }

    public function getFullNameAttribute(): string
    {
        return trim(
            $this->first_name . ' ' . $this->last_name
        );
    }
}
