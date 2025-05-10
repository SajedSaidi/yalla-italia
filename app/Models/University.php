<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class University extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
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

    public function documentDeadlines()
    {
        return $this->hasMany(DocumentDeadline::class);
    }
}
