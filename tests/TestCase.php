<?php

namespace Tests;

use App\Models\Nationality;
use App\Models\Qualification;
use App\Models\Student;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    protected function makeStudent(array $userAttributes = [], bool $approved = true, array $studentAttributes = []): Student
    {
        $user = User::factory()->student($approved)->create($userAttributes);

        return Student::create([
            'user_id' => $user->id,
            'phone' => '+39 333 000 0000',
            'date_of_birth' => '2000-01-01',
            'place_of_birth' => 'Beirut',
            'address' => 'Via Roma 1',
            'nationality_id' => $this->nationality()->id,
            ...$studentAttributes,
        ]);
    }

    protected function nationality(): Nationality
    {
        return Nationality::firstOrCreate(['code' => 'LB'], ['name' => 'Lebanese']);
    }

    protected function qualification(): Qualification
    {
        return Qualification::firstOrCreate(['code' => 'HS'], ['name' => 'High School Diploma']);
    }
}
