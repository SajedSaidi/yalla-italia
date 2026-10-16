<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Student;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New student'),
        ];
    }

    public function getTabs(): array
    {
        $approved = fn (bool $approved) => fn (Builder $query) => $query->whereHas('user', fn (Builder $query) => $query->where('is_approved', $approved));

        return [
            'all' => Tab::make('All students')
                ->badge(Student::count()),
            'pending' => Tab::make('Pending approval')
                ->icon('heroicon-o-clock')
                ->badge(StudentResource::pendingApprovalCount())
                ->badgeColor('warning')
                ->modifyQueryUsing($approved(false)),
            'approved' => Tab::make('Approved')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing($approved(true)),
        ];
    }
}
