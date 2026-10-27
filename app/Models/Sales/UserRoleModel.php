<?php

namespace App\Models\Sales;

use App\Models\Core\EmployeeModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRoleModel extends Model
{
    protected $table = 'sales.user_role';

    protected $primaryKey = 'employee_id';

    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'employee_id',
        'role_id'
    ];

    public function employee() : BelongsTo {
        return $this->belongsTo(EmployeeModel::class, 'employee_id', 'id');
    }

    public function role() : BelongsTo {
        return $this->belongsTo(RoleModel::class, 'role_id', 'id');
    }
}
