<?php

namespace App\Filament\Widgets;

use App\Models\DocumentDeadline;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Saade\FilamentFullCalendar\Actions\CreateAction;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class DocumentDeadlinesCalendar extends FullCalendarWidget
{
    /** 
     * Tell the widget which model to use when opening the “view” action.
     * This initializes the internal $record property properly. 
     */
    public Model|string|null $model = DocumentDeadline::class;

    protected static ?int $sort = 6;

    public function fetchEvents(array $info): array
    {
        $query = DocumentDeadline::query()
            ->with(['documentType', 'university', 'academicYear'])
            ->where('deadline', '>=', now()->subDays(30));

        return $query->get()
            ->map(function (DocumentDeadline $deadline) {
                $daysLeft = now()->diffInDays($deadline->deadline, false);

                return [
                    'id'          => $deadline->id,
                    'title'       => $deadline->formatted_title,
                    'start'       => $deadline->deadline,
                    'end'         => $deadline->deadline,
                    'color'       => match (true) {
                        $daysLeft <= 7  => '#EF4444',
                        $daysLeft <= 14 => '#F59E0B',
                        default         => '#10B981',
                    },
                    'description' => "Due in {$daysLeft} days\n{$deadline->notes}",
                ];
            })
            ->toArray();
    }

    protected function headerActions(): array
    {
        if (Auth::user()->isStudent()) {
            return [];
        }
        return [
            CreateAction::make(),
        ];
    }

    protected function modalActions(): array
    {
        return [];
    }

    public function getFormSchema(): array
    {
        return [
            Section::make()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('document_type_id')
                                ->label('Document Type')
                                ->relationship('documentType', 'name')
                                ->preload()
                                ->searchable()
                                ->required()
                                ->reactive(),

                            Select::make('university_id')
                                ->label('University')
                                ->relationship('university', 'name')
                                ->preload()
                                ->searchable()
                                ->required()
                                ->reactive(),

                            Select::make('academic_year_id')
                                ->label('Academic Year')
                                ->relationship('academicYear', 'name')
                                ->preload()
                                ->searchable()
                                ->required()
                                ->reactive(),

                            Select::make('education_level')
                                ->label('Education Level')
                                ->options([
                                    'single_cycle' => 'Single Cycle',
                                    'bachelor' => 'Bachelor',
                                    'master' => 'Master',
                                    'phd' => 'PhD',
                                ])
                                ->required()
                                ->reactive(),

                            DatePicker::make('deadline')
                                ->label('Deadline')
                                ->required()
                                ->displayFormat('d/m/Y')
                                ->format('Y-m-d'),
                        ]),

                    RichEditor::make('notes')
                        ->label('Notes')
                        ->disableToolbarButtons(['attachFiles'])
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'bulletList',
                            'orderedList',
                            'link',
                        ])
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'initialView'   => 'dayGridMonth',
            'initialDate'   => now(),
            'headerToolbar' => [
                'left'   => 'prev,next today',
                'center' => 'title',
                'right'  => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'dayMaxEvents'  => true,
            'navLinks'      => true,
        ];
    }
}
