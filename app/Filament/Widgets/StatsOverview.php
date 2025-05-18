<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use App\Models\Document;
use App\Models\Program;
use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        if (Auth::user()->isStudent()) {
            return $this->getStudentStats();
        }

        return $this->getAdminStats();
    }

    private function getStudentStats(): array
    {
        $student = Auth::user()->student;

        return [
            Stat::make('My Applications', $student->applications()->count())
                ->description('Total applications submitted')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->chart([3, 6, 9, 12, 15, 18])
                ->color('primary'),

            Stat::make('Pending Documents', $student->documents()->where('status', 'pending')->count())
                ->description('Documents awaiting review')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Accepted Applications', $student->applications()->where('status', 'accepted')->count())
                ->description('Successfully accepted applications')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }

    private function getAdminStats(): array
    {
        return [
            Stat::make('Total Students', Student::count())
                ->description('Active students in system')
                ->descriptionIcon('heroicon-m-users')
                ->chart([10, 20, 30, 40, 50, 60])
                ->color('info'),

            Stat::make('Active Programs', Program::count())
                ->description('Available programs')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->chart([5, 10, 15, 20, 25, 30])
                ->color('success'),

            Stat::make('Pending Applications', Application::where('status', 'pending')->count())
                ->description('Need review')
                ->descriptionIcon('heroicon-m-clock')
                ->chart([2, 4, 6, 8, 10, 12])
                ->color('warning'),
        ];
    }
}
