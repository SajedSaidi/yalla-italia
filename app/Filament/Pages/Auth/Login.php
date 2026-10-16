<?php

namespace App\Filament\Pages\Auth;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();

        // Flash set by EnsureUserIsApproved when a session is ended for an unapproved student.
        if ($message = session()->pull('error')) {
            Notification::make()
                ->title($message)
                ->warning()
                ->persistent()
                ->send();
        }
    }

    /**
     * The base form already includes the "Remember me" checkbox and passes it to
     * Auth::attempt(). On top of that, students still awaiting approval are refused
     * with a clear message instead of being silently logged out on the next request.
     */
    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        $user = Filament::auth()->user();

        if ($user && $user->isStudent() && ! $user->isApproved()) {
            Filament::auth()->logout();

            throw ValidationException::withMessages([
                'data.email' => 'Your account is pending approval. You will receive an email once it has been approved.',
            ]);
        }

        return $response;
    }
}
