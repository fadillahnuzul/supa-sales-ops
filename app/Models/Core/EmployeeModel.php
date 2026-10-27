<?php

namespace App\Models\Core;

use App\Models\Sales\InquiryModel;
use App\Models\Sales\UserRoleModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class EmployeeModel extends Authenticatable
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

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function canManagePrintAssets(): bool
    {
        return $this->is_active && in_array($this->username, config('access.asset_managers', []), true);
    }


    public function inquiries(): HasMany
    {
        return $this->hasMany(
            InquiryModel::class,
            'pic'
        );
    }

    public function segmentation() : HasMany {
        return $this->hasMany(EmployeeSegmentationModel::class, 'employee_id', 'id');
    }

    public function role() : HasOne {
        return $this->hasOne(UserRoleModel::class, 'employee_id', 'id');
    }

    public function getFullNameAttribute(): string
    {
        return trim(
            $this->first_name . ' ' . $this->last_name
        );
    }
}
