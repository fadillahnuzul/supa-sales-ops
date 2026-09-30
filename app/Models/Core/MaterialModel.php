<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialModel extends Model
{
    use SoftDeletes;

    protected $table = 'core.materials';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'name_indonesian',
    ];
}
