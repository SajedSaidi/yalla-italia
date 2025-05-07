<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class University extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'email',
        'phone',
        'description',
        'website_url'
    ];

    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
