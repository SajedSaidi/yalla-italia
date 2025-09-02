<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AdminApplicationChart;
use App\Filament\Widgets\DocumentsTimeline;
use App\Filament\Widgets\LatestApplications;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\StudentApplicationChart;
use App\Filament\Widgets\DocumentDeadlinesCalendar;
use App\Filament\Widgets\MissingDocumentsAlert;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort = -2;

    protected function getActions(): array
    {
        // Remove all manual notification actions
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            // StatsOverview::class,
            // // Auth::user()->isStudent() ? StudentApplicationChart::class : AdminApplicationChart::class,
            // DocumentDeadlinesCalendar::class,
            // MissingDocumentsAlert::class,

        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            // DocumentsTimeline::class,
            // LatestApplications::class,
        ];
    }
}
