<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class StudentApplicationChart extends ChartWidget
{
    protected static ?string $heading = 'My Applications Over Time';
    protected static ?int $sort = 12;

    protected function getData(): array
    {
        $data = Trend::model(Application::class)
            ->between(
                start: now()->subMonths(6),
                end: now(),
            )
            // ->where('student_id', Auth::user()->student->id)
            ->perMonth()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'My Applications',
                    'data' => $data->map(fn(TrendValue $value) => $value->aggregate),
                    'backgroundColor' => '#10B981',
                ]
            ],
            'labels' => $data->map(fn(TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
