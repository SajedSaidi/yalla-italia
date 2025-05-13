<?php

namespace App\Filament\Pages;

use App\Models\Student;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\{
    DatePicker,
    Grid,
    RichEditor,
    Section,
    TextInput,
};
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Profile extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'My Profile';
    protected static ?string $slug            = 'profile';
    protected static string $view             = 'filament.pages.profile';

    public ?User $user;

    public function mount(): void
    {
        abort_unless(Auth::user()->isStudent(), 403);

        $this->user = User::with('student')->find(Auth::id());

        $this->form->fill([
            'name'           => $this->user->name,
            'email'          => $this->user->email,
            'phone'          => $this->user->student->phone,
            'date_of_birth'  => $this->user->student->date_of_birth,
            'address'        => $this->user->student->address,
            'nationality'    => $this->user->student->nationality,
            'qualifications' => $this->user->student->qualifications,
        ]);
    }

    /**
     * Define your form fields here.
     */
    protected function getFormSchema(): array
    {
        return [
            Section::make('Personal Information')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Full Name')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('email')
                                ->label('Email Address')
                                ->email()
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('student.phone')
                                ->label('Phone Number')
                                ->tel()
                                ->required()
                                ->maxLength(20),

                            DatePicker::make('student.date_of_birth')
                                ->label('Date of Birth')
                                ->required()
                                ->maxDate(now()),

                            TextInput::make('student.address')
                                ->label('Address')
                                ->maxLength(255),

                            TextInput::make('student.nationality')
                                ->label('Nationality')
                                ->maxLength(100),
                        ]),

                    RichEditor::make('student.qualifications')
                        ->label('Qualifications')
                        ->disableToolbarButtons(['attachFiles'])
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'bulletList',
                            'orderedList',
                            'link',
                        ])
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * Handle the Save button.
     */
    public function save(): void
    {
        $data = $this->form->getState();

        try {
            Auth::user()->update([
                'name' => $data['name'],
            ]);

            $this->user->student->update([
                'phone'          => $data['phone'],
                'date_of_birth'  => $data['date_of_birth'],
                'address'        => $data['address'],
                'nationality'    => $data['nationality'],
                'qualifications' => $data['qualifications'],
            ]);

            Notification::make()
                ->success()
                ->title('Profile Updated')
                ->body('Your profile has been updated successfully.')
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('There was an error updating your profile.')
                ->send();
        }
    }

    /**
     * Only expose a single “Save Changes” button.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->action('save'),
        ];
    }

    /**
     * Only students should see this link.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check() && Auth::user()->isStudent();
    }
}
