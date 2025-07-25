<?php

namespace App\Filament\Pages\Auth;

use App\Mail\NewUserRegistration;
use App\Models\Nationality;
use App\Models\Student;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Register extends BaseRegister implements HasForms
{
    use InteractsWithForms;

    // protected static string $view = 'filament.pages.auth.register';
    protected ?string $maxWidth = 'max-w-3xl';

    protected static ?string $title = 'Student / Staff Registration';

    protected static bool $shouldRegisterNavigationItem = false;

    // User details
    public $role;
    public $name;
    public $email;
    public $password;

    // Student details
    public $phone;
    public $date_of_birth;
    public $place_of_birth;
    public $address;
    public $nationality_id;
    public $qualifications;

    public function mount(): void
    {
        parent::mount();

        // Initialize the form
        $this->form->fill();
    }

    public function getFormSchema(): array
    {
        return [
            Wizard::make([
                Wizard\Step::make('Profile')
                    ->icon('heroicon-o-user')
                    ->description('Tell us about yourself.')
                    ->schema([
                        Hidden::make('role')->default('student'),
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(191)
                            ->placeholder('Enter user name')
                            ->columnSpan(1),
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->unique(table: 'users', column: 'email')
                            ->disabledOn('edit')
                            ->required()
                            ->placeholder('Enter email address')
                            ->columnSpan(1),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->maxLength(191)
                            ->placeholder('Enter password')
                            ->helperText('Password must be at least 8 characters')
                            ->columnSpan(1),
                    ]),

                Wizard\Step::make('Student Details')
                    ->icon('heroicon-o-academic-cap')
                    ->description('Provide your student details.')
                    ->schema([
                        Grid::make(2)                           // ← two equal columns
                            ->schema([
                                TextInput::make('phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->required()
                                    ->maxLength(20)
                                    ->columnSpan(1),          // ← spans 1 of 2 columns

                                DatePicker::make('date_of_birth')
                                    ->label('Date of Birth')
                                    ->required()
                                    ->maxDate(now())
                                    ->displayFormat('d/m/Y')
                                    ->format('Y-m-d') // Keep database format as Y-m-d
                                    ->columnSpan(1),

                                TextInput::make('place_of_birth')
                                    ->label('Place of Birth')
                                    ->required() // Add required
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                TextInput::make('address')
                                    ->label('Address')
                                    ->required() // Already required
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                Select::make('nationality_id')
                                    ->label('Nationality')
                                    ->preload()
                                    ->searchable()
                                    ->required()
                                    ->options(function () {
                                        return Nationality::all()
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    }),

                                Select::make('qualifications')
                                    ->label('Qualifications')
                                    ->required()
                                    ->searchable()
                                    ->options(Student::getQualificationOptions())
                                    ->columnSpan(2),       // ← spans both columns
                            ]),
                    ]),

            ])
                ->skippable(false)
        ];
    }

    protected function handleRegistration(array $data): Model
    {
        // 1) Create the User (not approved by default)
        $user = User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'password'    => bcrypt($data['password']),
            'role'        => $data['role'],
            'is_approved' => false, // Requires admin approval
        ]);

        // 2) Create related Student record
        Student::create([
            'user_id'        => $user->id,
            'phone'          => $data['phone'],
            'date_of_birth'  => $data['date_of_birth'],
            'place_of_birth' => $data['place_of_birth'] ?? null,
            'address'        => $data['address'],
            'nationality_id' => $data['nationality_id'],
            'qualifications' => $data['qualifications'],
        ]);

        // 3) Send notification to admins
        $this->notifyAdmins($user);

        // 4) Show success message to user
        Notification::make()
            ->title('Registration Successful')
            ->body('Your account has been created and is pending approval. You will receive an email once approved.')
            ->success()
            ->send();

        return $user;
    }

    protected function notifyAdmins(User $user): void
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->queue(new NewUserRegistration($user));
        }
    }
    // Prevent automatic login after registration
    protected function getRedirectUrl(): string
    {
        return $this->getLoginUrl();
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->title($exception->getMessage())
            ->danger()
            ->send();
    }
}
