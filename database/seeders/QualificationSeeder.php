<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Qualification;

class QualificationSeeder extends Seeder
{
    public function run(): void
    {
        $qualifications = [
            ['name' => 'Bachelor Degree', 'code' => 'bachelor_degree'],
            ['name' => 'Masters Degree', 'code' => 'masters_degree'],
            ['name' => 'Lebanese Baccalaureate', 'code' => 'lebanese_baccalaureate'],
            ['name' => 'Technical Baccalaureate', 'code' => 'technical_baccalaureate'],
            ['name' => 'High School Diploma', 'code' => 'high_school_diploma'],
            ['name' => 'Freshmen', 'code' => 'freshmen'],
        ];

        foreach ($qualifications as $qualification) {
            Qualification::create($qualification);
        }
    }
}
