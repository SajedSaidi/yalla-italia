<?php

namespace App\Filament\Resources;

use App\Filament\Filters\RegisteredBetweenFilter;
use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers\ApplicationsRelationManager;
use App\Filament\Resources\StudentResource\RelationManagers\DocumentsRelationManager;
use App\Models\Nationality;
use App\Models\Student;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * One place to manage a student: the login account (users table) and the
 * student profile (students table) are created and edited together.
 */
class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Students';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->isManagerOrAdmin();
    }

    public static function canCreate(): bool
    {
        return Auth::check() && Auth::user()->isManagerOrAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::check() && Auth::user()->isManagerOrAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::check() && $record->user && Auth::user()->can('delete', $record->user);
    }

    public static function canDeleteAny(): bool
    {
        return Auth::check() && Auth::user()->isManagerOrAdmin();
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::pendingApprovalCount();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Students awaiting approval';
    }

    public static function pendingApprovalCount(): int
    {
        return Student::query()->whereHas('user', fn (Builder $query) => $query->where('is_approved', false))->count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user.approvedBy', 'nationality']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Account')
                    ->description('Login details for the student portal.')
                    ->icon('heroicon-o-key')
                    ->statePath('user')
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(191),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(191)
                            ->unique(table: User::class, column: 'email', ignorable: fn (?Student $record) => $record?->user),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->minLength(8)
                            ->maxLength(191)
                            ->placeholder(fn (string $operation) => $operation === 'create' ? 'At least 8 characters' : 'Leave blank to keep the current password')
                            ->hiddenOn('view'),
                        Toggle::make('is_approved')
                            ->label('Approved')
                            ->helperText('Approved students can sign in. They are emailed automatically when approved.')
                            ->default(true)
                            ->inline(false),
                        Placeholder::make('approval_info')
                            ->label('Approval')
                            ->content(fn (?Student $record) => $record?->user?->is_approved
                                ? 'Approved ' . ($record->user->approved_at?->format('M d, Y \a\t H:i') ?? '') . ($record->user->approvedBy ? " by {$record->user->approvedBy->name}" : '')
                                : 'Pending approval')
                            ->hiddenOn('create'),
                    ])
                    ->columns(2),

                Section::make('Student Details')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->required()
                                    ->maxLength(20),
                                DatePicker::make('date_of_birth')
                                    ->label('Date of Birth')
                                    ->required()
                                    ->maxDate(now())
                                    ->displayFormat('d/m/Y')
                                    ->format('Y-m-d'),
                                TextInput::make('place_of_birth')
                                    ->label('Place of Birth')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('address')
                                    ->label('Address')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('nationality_id')
                                    ->label('Nationality')
                                    ->preload()
                                    ->searchable()
                                    ->required()
                                    ->options(fn () => Nationality::query()->orderBy('name')->pluck('name', 'id')->toArray()),
                                Select::make('qualifications')
                                    ->label('Qualifications')
                                    ->multiple()
                                    ->relationship('qualifications', 'name')
                                    ->preload()
                                    ->searchable()
                                    ->required()
                                    ->columnSpanFull(),
                                Select::make('languageCertificates')
                                    ->label('Language Certificates')
                                    ->multiple()
                                    ->relationship('languageCertificates', 'name')
                                    ->preload()
                                    ->searchable()
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Student')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->sortable()
                    ->searchable()
                    ->copyable(),
                IconColumn::make('user.is_approved')
                    ->label('Approved')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('date_of_birth')
                    ->label('Date of Birth')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('nationality.name')
                    ->label('Nationality')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('qualifications.name')
                    ->badge()
                    ->color('primary')
                    ->label('Qualifications')
                    ->toggleable(),
                TextColumn::make('languageCertificates.name')
                    ->badge()
                    ->color('success')
                    ->label('Language Certificates')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                TextColumn::make('user.approved_at')
                    ->label('Approved At')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.approvedBy.name')
                    ->label('Approved By')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('approval')
                    ->label('Approval Status')
                    ->placeholder('All students')
                    ->trueLabel('Approved')
                    ->falseLabel('Pending approval')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('user', fn (Builder $query) => $query->where('is_approved', true)),
                        false: fn (Builder $query) => $query->whereHas('user', fn (Builder $query) => $query->where('is_approved', false)),
                        blank: fn (Builder $query) => $query,
                    ),

                Tables\Filters\SelectFilter::make('nationality_id')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->options(fn () => Nationality::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->label('Nationality'),

                Tables\Filters\SelectFilter::make('qualifications')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->relationship('qualifications', 'name')
                    ->label('Qualifications'),

                Tables\Filters\SelectFilter::make('languageCertificates')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->relationship('languageCertificates', 'name')
                    ->label('Language Certificates'),

                RegisteredBetweenFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->iconSize('lg')
                    ->hiddenLabel()
                    ->tooltip('Approve')
                    ->color('success')
                    ->visible(fn (Student $record) => $record->user && ! $record->user->is_approved)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Student $record) => "Approve {$record->user->name}?")
                    ->modalDescription('The student will be able to sign in and will receive a confirmation email.')
                    ->action(fn (Student $record) => static::approve($record)),
                Tables\Actions\Action::make('unapprove')
                    ->icon('heroicon-o-no-symbol')
                    ->iconSize('lg')
                    ->hiddenLabel()
                    ->tooltip('Unapprove')
                    ->color('warning')
                    ->visible(fn (Student $record) => $record->user?->is_approved)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Student $record) => "Unapprove {$record->user->name}?")
                    ->modalDescription('The student will no longer be able to sign in until approved again.')
                    ->action(fn (Student $record) => static::unapprove($record)),
                Tables\Actions\ViewAction::make()->iconSize('lg')->hiddenLabel()->tooltip('View'),
                Tables\Actions\EditAction::make()->iconSize('lg')->hiddenLabel()->tooltip('Edit'),
                static::configureDeleteAction(Tables\Actions\DeleteAction::make())->iconSize('lg')->hiddenLabel()->tooltip('Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approve selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $pending = $records->filter(fn (Student $record) => $record->user && ! $record->user->is_approved);
                            $pending->each(fn (Student $record) => $record->user->approve(Auth::user()));

                            Notification::make()
                                ->title('Students approved')
                                ->body("{$pending->count()} student(s) approved and notified by email.")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalDescription('This permanently deletes the selected students, including their login accounts, documents and applications.')
                        ->using(function (Collection $records) {
                            DB::transaction(fn () => $records
                                ->filter(fn (Student $record) => static::canDelete($record))
                                ->each(fn (Student $record) => $record->user->delete()));
                        }),
                ]),
            ]);
    }

    /**
     * Deleting a student removes the whole account: login, profile, documents (and files) and applications.
     */
    public static function configureDeleteAction(Tables\Actions\DeleteAction|\Filament\Actions\DeleteAction $action)
    {
        return $action
            ->modalDescription('This permanently deletes the student\'s login account, profile, documents and applications.')
            ->using(fn (Student $record) => DB::transaction(fn () => (bool) $record->user->delete()));
    }

    public static function approve(Student $record): void
    {
        $record->user->approve(Auth::user());

        Notification::make()
            ->title('Student approved')
            ->body("{$record->user->name} can now sign in and has been notified by email.")
            ->success()
            ->send();
    }

    public static function unapprove(Student $record): void
    {
        $record->user->unapprove();

        Notification::make()
            ->title('Student unapproved')
            ->body("{$record->user->name} can no longer sign in.")
            ->warning()
            ->send();
    }

    public static function getRelations(): array
    {
        return [
            DocumentsRelationManager::class,
            ApplicationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'   => Pages\ListStudents::route('/'),
            'create'  => Pages\CreateStudent::route('/create'),
            'edit'    => Pages\EditStudent::route('/{record}/edit'),
            'view'   => Pages\ViewStudent::route('/{record}'),
        ];
    }
}
