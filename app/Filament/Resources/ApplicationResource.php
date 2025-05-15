<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationResource\Pages;
use App\Filament\Resources\ApplicationResource\RelationManagers;
use App\Models\Application;
use App\Models\Program;
use App\Models\Student;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Applications';
    protected static ?string $navigationGroup = 'Academics';

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->isStudent();
    }

    public static function canCreate(): bool
    {
        return Auth::check() && Auth::user()->isManagerOrAdmin();
    }

    public static function canEdit($record): bool
    {
        return Auth::check() && Auth::user()->isManagerOrAdmin();
    }

    public static function canDelete($record): bool
    {
        return Auth::check() && Auth::user()->isManagerOrAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('student_id')
                    ->default(Auth::user()->student->id),
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
                                            ->disabled()
                                            ->searchable()
                                            ->required(),

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.user.name')
                    ->label('Student')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('program.composite_title')
                    ->label('Program')
                    ->sortable()
                    ->searchable(),
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
                        'paid' => 'success',     // Green - Completed payment
                        'unpaid' => 'danger',    // Red - Payment required
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',   // Orange - Needs attention
                        'accepted' => 'success',  // Green - Positive outcome
                        'rejected' => 'danger',   // Red - Negative outcome
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
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

                // Tables\Filters\SelectFilter::make('program_id')
                //     ->multiple()
                //     ->preload()
                //     ->searchable()
                //     ->options(fn() => Program::all()->pluck('composite_title', 'id')->toArray())
                //     ->label('Program'),
            ])
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->where('student_id', Auth::user()->student->id);
            })
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()->iconSize('lg')->hiddenLabel(),
                Tables\Actions\EditAction::make()->iconSize('lg')->hiddenLabel(),
                // Tables\Actions\DeleteAction::make()->iconSize('lg')->hiddenLabel(),
            ])
            ->bulkActions([
                // // Tables\Actions\DeleteBulkAction::make(),

            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            // 'create' => Pages\CreateApplication::route('/create'),
            // 'edit' => Pages\EditApplication::route('/{record}/edit'),
        ];
    }
}
