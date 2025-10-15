<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Mail\UserApproved;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->isManagerOrAdmin();
    }

    public static function canCreate(): bool
    {
        return Auth::check() && Auth::user()->isManagerOrAdmin();
    }

    public static function canView(Model $record): bool
    {
        return Auth::check() && Auth::user()->isManagerOrAdmin();
    }

    public static function canEdit($record): bool
    {
        return Auth::check() && Auth::user()->isManagerOrAdmin();
    }

    public static function canDelete($record): bool
    {
        return Auth::check() && Auth::user()->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(191)
                            ->placeholder('Enter user name')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->disabledOn('edit')
                            ->required()
                            ->placeholder('Enter email address')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->visibleOn('create')
                            ->required()
                            ->minLength(8)
                            ->maxLength(191)
                            ->placeholder('Enter password')
                            ->helperText('Password must be at least 8 characters')
                            ->columnSpan(1),
                        Forms\Components\Select::make('role')
                            ->options(
                                function () {
                                    return Auth::user()->isManager() ? [
                                        'student' => 'Student'
                                    ] : [
                                        'admin' => 'Admin',
                                        'manager' => 'Manager',
                                        'student' => 'Student',
                                    ];
                                }
                            )
                            ->required()
                            ->default('user')
                            ->label('User Role')
                            ->columnSpan(1),
                    ])->columns(2),

                Forms\Components\Section::make('Approval Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_approved')
                            ->label('User Approved')
                            ->helperText('Toggle to approve/unapprove this user')
                            ->visible(fn() => Auth::user()->isManagerOrAdmin())
                            ->afterStateUpdated(function ($state, $record, $set) {
                                if ($state && $record && !$record->is_approved) {
                                    // User was just approved
                                    $record->update([
                                        'is_approved' => true,
                                        'approved_at' => now(),
                                        'approved_by' => Auth::id(),
                                    ]);

                                    // Send approval email
                                    Mail::to($record->email)->queue(new UserApproved($record));

                                    \Filament\Notifications\Notification::make()
                                        ->title('User Approved')
                                        ->success()
                                        ->body("User {$record->name} has been approved and notified via email.")
                                        ->send();
                                }
                            }),

                        Forms\Components\TextInput::make('approved_at')
                            ->label('Approved At')
                            ->disabled()
                            ->visible(fn($get) => $get('is_approved') && Auth::user()->isManagerOrAdmin())
                            ->formatStateUsing(fn($state) => $state ? \Carbon\Carbon::parse($state)->format('M d, Y \a\t H:i') : null),

                        Forms\Components\Select::make('approved_by')
                            ->label('Approved By')
                            ->disabled()
                            ->visible(fn($get) => $get('is_approved') && Auth::user()->isManagerOrAdmin())
                            ->relationship('approvedBy', 'name'),
                    ])
                    ->visible(fn() => Auth::user()->isManagerOrAdmin())
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'success' => 'admin',
                        'warning' => 'manager',
                        'primary' => 'student',
                    ]),
                Tables\Columns\IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Student Approval'),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Approved By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'manager' => 'Manager',
                        'student' => 'Student',
                    ]),
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approval Status')
                    ->placeholder('All users')
                    ->trueLabel('Approved users')
                    ->falseLabel('Pending approval'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(User $record) => !$record->is_approved && Auth::user()->isManagerOrAdmin())
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->update([
                            'is_approved' => true,
                            'approved_at' => now(),
                            'approved_by' => Auth::id(),
                        ]);

                        // Send approval email to user
                        Mail::to($record->email)->queue(new UserApproved($record));

                        \Filament\Notifications\Notification::make()
                            ->title('User Approved')
                            ->success()
                            ->body("User {$record->name} has been approved and notified.")
                            ->send();
                    }),
                Action::make('unapprove')
                    ->label('Unapprove')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(User $record) => $record->is_approved && Auth::user()->isManagerOrAdmin())
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->update([
                            'is_approved' => false,
                            'approved_at' => null,
                            'approved_by' => null,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('User Unapproved')
                            ->warning()
                            ->body("User {$record->name} has been unapproved.")
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn() => Auth::user()->isManagerOrAdmin())
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $approvedCount = 0;
                            foreach ($records as $record) {
                                if (!$record->is_approved) {
                                    $record->update([
                                        'is_approved' => true,
                                        'approved_at' => now(),
                                        'approved_by' => Auth::id(),
                                    ]);
                                    Mail::to($record->email)->queue(new UserApproved($record));
                                    $approvedCount++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Users Approved')
                                ->success()
                                ->body("{$approvedCount} user(s) have been approved and notified.")
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
