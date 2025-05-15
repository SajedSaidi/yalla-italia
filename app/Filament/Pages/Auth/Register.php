<?php

namespace App\Filament\Pages\Auth;

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
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

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
    public $address;
    public $nationality;
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
                                    ->columnSpan(1),

                                TextInput::make('address')
                                    ->label('Address')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                TextInput::make('nationality')
                                    ->label('Nationality')
                                    ->required()
                                    ->maxLength(100)
                                    ->columnSpan(1),

                                RichEditor::make('qualifications')
                                    ->label('Qualifications')
                                    ->required()
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
                                    ->columnSpan(2),       // ← spans both columns
                            ]),
                    ]),


            ])
        ];
    }

    protected function handleRegistration(array $data): Model
    {
        // 1) Create the User
        $user = parent::handleRegistration([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'role'     => $data['role'],
        ]);

        // 2) Create related Student record
        Student::create([
            'user_id'       => $user->id,
            'phone'         => $data['phone'],
            'date_of_birth' => $data['date_of_birth'],
            'address'       => $data['address'],
            'nationality'   => $data['nationality'],
            'qualifications' => $data['qualifications'],
        ]);

        return $user;
    }
}
