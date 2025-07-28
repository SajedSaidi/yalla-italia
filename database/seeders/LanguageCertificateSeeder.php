<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LanguageCertificate;

class LanguageCertificateSeeder extends Seeder
{
    public function run(): void
    {
        $certificates = [
            // English Certificates
            ['name' => 'IELTS', 'code' => 'ielts'],
            ['name' => 'TOEFL', 'code' => 'toefl'],
            ['name' => 'TOEIC', 'code' => 'toeic'],
            ['name' => 'Cambridge English', 'code' => 'cambridge'],
            ['name' => 'Duolingo English Test', 'code' => 'duolingo'],

            // Italian Certificates
            ['name' => 'CILS (Certification of Italian as a Foreign Language)', 'code' => 'cils'],
            ['name' => 'CELI (Certificate of Knowledge of Italian Language)', 'code' => 'celi'],
            ['name' => 'PLIDA (Dante Alighieri Project)', 'code' => 'plida'],
            ['name' => 'IT (Certificate of Italian)', 'code' => 'it_cert'],
            ['name' => 'AIL (Italian Academy of Language)', 'code' => 'ail'],

            // Medium of Instruction
            ['name' => 'English Medium School Certificate', 'code' => 'english_medium_school'],
            ['name' => 'English Medium University Certificate', 'code' => 'english_medium_university'],
            ['name' => 'Italian Medium School Certificate', 'code' => 'italian_medium_school'],
            ['name' => 'Italian Medium University Certificate', 'code' => 'italian_medium_university'],
            ['name' => 'Bilingual Education Certificate', 'code' => 'bilingual_education'],
        ];

        foreach ($certificates as $certificate) {
            LanguageCertificate::create($certificate);
        }
    }
}
