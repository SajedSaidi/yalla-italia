<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Major extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    protected $fillable = [
        'name',
        'type',
        'description',
    ];

    public static function typeLabels(): array
    {
        return [
            'single_cycle' => 'Single Cycle',
            'bachelor'     => 'Bachelor',
            'master'       => 'Master',
            'phd'          => 'PhD',
        ];
    }

    public function getCompositeTitleAttribute(): string
    {
        $name   = $this->name;
        $type = $this->type;
        $type = self::typeLabels()[$type] ?? $type;
        return "{$name} – {$type}";
    }

    public function programs()
    {
        return $this->hasMany(Program::class);
    }

    public function languages()
    {
        return $this->belongsToMany(Language::class);
    }
}
