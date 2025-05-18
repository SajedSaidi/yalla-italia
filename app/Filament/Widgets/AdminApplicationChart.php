<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class AdminApplicationChart extends ChartWidget
{
    protected static ?string $heading = 'Applications Overview';
    protected static ?int $sort = 10;

    protected function getData(): array
    {
        $applications = Trend::model(Application::class)
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->count();

        $acceptedApplications = Trend::model(Application::class)
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            // ->where('status', 'accepted')
            ->perMonth()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Total Applications',
                    'data' => $applications->map(fn(TrendValue $value) => $value->aggregate),
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Accepted Applications',
                    'data' => $acceptedApplications->map(fn(TrendValue $value) => $value->aggregate),
                    'borderColor' => '#60A5FA',
                    'backgroundColor' => 'rgba(96, 165, 250, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $applications->map(fn(TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
