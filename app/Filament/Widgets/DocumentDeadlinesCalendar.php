<?php

namespace App\Filament\Widgets;

use App\Models\DocumentDeadline;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
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

        if (Auth::user()->isStudent()) {
            $query->where('education_level', Auth::user()->student->current_education_level);
        }

        return $query->get()
            ->map(function (DocumentDeadline $deadline) {
                $daysLeft = now()->diffInDays($deadline->deadline, false);

                return [
                    'id'          => $deadline->id,
                    'title'       => "{$deadline->documentType->name} - {$deadline->university->name}",
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
