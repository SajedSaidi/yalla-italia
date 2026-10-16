<?php

namespace App\Filament\Filters;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class RegisteredBetweenFilter
{
    public static function make(string $column = 'created_at'): Filter
    {
        return Filter::make('registered_between')
            ->form([
                DatePicker::make('from')->label('Registered from'),
                DatePicker::make('until')->label('Registered until'),
            ])
            ->query(fn (Builder $query, array $data) => $query
                ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate($query->qualifyColumn($column), '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate($query->qualifyColumn($column), '<=', $date)))
            ->indicateUsing(function (array $data): array {
                $indicators = [];

                if ($data['from'] ?? null) {
                    $indicators[] = 'Registered from ' . Carbon::parse($data['from'])->format('M d, Y');
                }

                if ($data['until'] ?? null) {
                    $indicators[] = 'Registered until ' . Carbon::parse($data['until'])->format('M d, Y');
                }

                return $indicators;
            });
    }
}
