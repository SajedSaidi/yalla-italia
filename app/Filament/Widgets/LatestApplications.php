<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class LatestApplications extends BaseWidget
{
    protected static ?int $sort = 12;

    // protected int $defaultTableRecordsPerPageSelectOption = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Auth::user()->isStudent()
                    ? Application::query()->where('student_id', Auth::user()->student->id)
                    : Application::query()
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('student.user.name')
                    ->label('Student')
                    ->searchable()
                    ->visible(!Auth::user()->isStudent()),
                TextColumn::make('program.composite_title')
                    ->label('Program')
                    ->searchable(),
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'accepted',
                        'danger' => 'rejected',
                    ]),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ]);
    }
}
