<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class SterilizationModel extends Model
{
    protected $table = 'core.sterilizations';

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];
}
