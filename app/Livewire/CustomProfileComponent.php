<?php

namespace App\Livewire;

use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput as FilamentTextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section as FilamentSection;
use Filament\Forms\Components\Grid as FilamentGrid;
use Filament\Forms\Components\TextInput as Input;
use Filament\Forms\Components\DatePicker as DP;
use Filament\Forms\Components\RichEditor as RE;
use Filament\Forms\Components\Section as S;
use Filament\Forms\Components\Grid as G;
use Filament\Forms\Components\TextInput as TI;
use Filament\Forms\Components\DatePicker as DtP;
use Filament\Forms\Components\RichEditor as REditor;
use Filament\Forms\Components\Fieldset;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use App\Models\Student;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Joaopaulolndev\FilamentEditProfile\Concerns\HasSort;

class CustomProfileComponent extends Component implements HasForms
{
    use InteractsWithForms;
    use HasSort;

    public ?array $data = [];

    protected static int $sort = 1;

    public function mount(): void
    {
        $student = Auth::user()->student;
        if (! $student) {
            // Optionally create a student record if not existing
            $student = Student::create(['user_id' => Auth::id()]);
        }
        $this->data = $student->toArray();
        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Student Profile')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->required()
                                    ->maxLength(20),

                                DatePicker::make('date_of_birth')
                                    ->label('Date of Birth')
                                    ->required()
                                    ->maxDate(now()),

                                TextInput::make('address')
                                    ->label('Address')
                                    ->maxLength(255),

                                TextInput::make('nationality')
                                    ->label('Nationality')
                                    ->maxLength(100),
                            ]),

                        RichEditor::make('qualifications')
                            ->label('Qualifications')
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
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $formData = $this->form->getState();

        $student = Auth::user()->student;
        $student->fill($formData);
        $student->save();

        Notification::make()
            ->title('Profile Updated')
            ->success()
            ->body('Your profile has been successfully updated.')
            ->send();
    }

    public function render(): View
    {
        return view('livewire.custom-profile-component');
    }

    public static function getTitle(): string
    {
        return 'Student Information';
    }

    public static function getSort(): int
    {
        return 1;
    }

    public static function getIcon(): ?string
    {
        return 'heroicon-o-academic-cap';
    }
}
