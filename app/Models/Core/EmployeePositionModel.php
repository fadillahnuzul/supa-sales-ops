<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class EmployeePositionModel extends Model
{
    protected $table = 'core.employee_positions';

    protected $fillable = [
        'employee_id',
        'position_id',
        'start_date',
        'end_date',
    ];

    public function employee()
    {
        return $this->belongsTo(EmployeeModel::class, 'employee_id', 'id');
    }

    public function position()
    {
        return $this->belongsTo(PositionModel::class, 'position_id', 'id');
    }
}
