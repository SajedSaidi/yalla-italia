<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use App\Models\Application;
use App\Models\Program;
use App\Models\Student;
use Closure;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;

class ApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'applications';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('student_id')
                    ->default(fn($livewire) => $livewire->ownerRecord->id),
                Section::make()
                    ->schema([
                        Group::make()
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('program_id')
                                            ->label('Program')
                                            ->relationship('program', 'composite_title')
                                            ->options(fn() => Program::all()->pluck('composite_title', 'id')->toArray())
                                            ->preload()
                                            ->searchable()
                                            ->required()
                                            ->rules([
                                                // Filament's "Get" helper lets us read other fields' values:
                                                fn(Get $get): Closure => function (
                                                    string  $attribute,
                                                    $value,
                                                    Closure $fail,
                                                ) use ($get) {
                                                    $student_id = $get('student_id');
                                                    $program_id = $get('program_id');
                                                    // Don’t validate until all three IDs are chosen
                                                    if (! $student_id || ! $program_id) {
                                                        return;
                                                    }

                                                    $query = Application::query()
                                                        ->where('student_id', $student_id)
                                                        ->where('program_id', $program_id);

                                                    // On edit, ignore the current record:
                                                    if ($get('id')) {
                                                        $query->where('id', '!=', $get('id'));
                                                    }

                                                    if ($query->exists()) {
                                                        $fail('Student already applied to this program!');
                                                    }
                                                },
                                            ]),


                                        Select::make('payment_status')
                                            ->label('Payment Status')
                                            ->options([
                                                'paid' => 'Paid',
                                                'unpaid' => 'Unpaid',
                                            ])
                                            ->default('unpaid')
                                            ->required(),

                                        Select::make('status')
                                            ->label('Status')
                                            ->options([
                                                'pending' => 'Pending',
                                                'accepted' => 'Accepted',
                                                'rejected' => 'Rejected',
                                            ])
                                            ->default('pending')
                                            ->required(),


                                        RichEditor::make('notes')
                                            ->label('Notes (Optional)')
                                            ->disableToolbarButtons(['attachFiles'])
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'underline',
                                                'strike',
                                                'h2',
                                                'h3',
                                                'bulletList',
                                                'orderedList',
                                                'link',
                                                'undo',
                                                'redo',
                                            ])
                                            ->disableGrammarly()
                                            ->columnSpanFull()
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('application_id')
            ->columns([
                TextColumn::make('program.composite_title')
                    ->label('Program')
                    ->sortable(),
                TextColumn::make('program.application_fee')
                    ->label('Application Fee')
                    ->money('EUR')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('payment_status')
                    ->label('Payment Status')
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',     // Green - Payment completed
                        'unpaid' => 'danger',    // Red - Payment needed
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',   // Orange - Needs review
                        'accepted' => 'success',  // Green - Approved
                        'rejected' => 'danger',   // Red - Denied
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Applied At')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                    ]),

                // filter by payment status
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'paid' => 'Paid',
                        'unpaid' => 'Unpaid',
                    ]),

                // filter by program
                Tables\Filters\SelectFilter::make('program_id')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->options(fn() => Program::all()->pluck('composite_title', 'id')->toArray())
                    ->label('Program'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconSize('lg')->hiddenLabel(),
                Tables\Actions\EditAction::make()->iconSize('lg')->hiddenLabel(),
                Tables\Actions\DeleteAction::make()->iconSize('lg')->hiddenLabel(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),

            ]);
    }
}
