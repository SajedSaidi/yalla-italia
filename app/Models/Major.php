<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Major extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'description',
    ];

    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
