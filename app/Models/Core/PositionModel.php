<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class PositionModel extends Model
{
    protected $table = 'core.positions';

    protected $fillable = [
        'name',
        'division_id',
    ];

    public function Employees()
    {
        return $this->hasMany(EmployeePositionModel::class, 'position_id', 'id');
    }
}
