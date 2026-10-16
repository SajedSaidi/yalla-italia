<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteAction as TableDeleteAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->defineDocumentGates();
        $this->embedEmailLogo();
        $this->handleBlockedDeletes();
    }

    protected function defineDocumentGates(): void
    {
        Gate::define('download-document', function (User $user, Document $document): bool {
            if ($user->isManagerOrAdmin()) {
                return true;
            }

            return $user->isStudent()
                && $user->student !== null
                && $document->student_id === $user->student->id;
        });

        Gate::define('download-student-documents', fn (User $user): bool => $user->isManagerOrAdmin());
    }

    /**
     * Email templates reference the public logo URL (so previews work in a browser).
     * When an email is actually sent, attach the logo inline and point to it with a
     * cid: reference, so it shows in every inbox even when APP_URL isn't public.
     */
    protected function embedEmailLogo(): void
    {
        Event::listen(MessageSending::class, function (MessageSending $event) {
            $logoPath = public_path('images/logo-email.png');
            $logoUrl = asset('images/logo-email.png');
            $html = $event->message->getHtmlBody();

            if (! is_string($html) || ! str_contains($html, $logoUrl) || ! is_file($logoPath)) {
                return;
            }

            $event->message
                ->embedFromPath($logoPath, 'yalla-logo', 'image/png')
                ->html(str_replace($logoUrl, 'cid:yalla-logo', $html));
        });
    }

    /**
     * Most foreign keys are "restrict": deleting e.g. a university that still has programs
     * must show a clear message instead of an error page.
     */
    protected function handleBlockedDeletes(): void
    {
        $deleteSafely = function (Model $record): bool {
            try {
                return (bool) $record->delete();
            } catch (QueryException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }

                Notification::make()
                    ->title('This record cannot be deleted')
                    ->body('It is still used by other records. Remove or reassign those first, then try again.')
                    ->danger()
                    ->send();

                return false;
            }
        };

        TableDeleteAction::configureUsing(fn (TableDeleteAction $action) => $action->using($deleteSafely));
        DeleteAction::configureUsing(fn (DeleteAction $action) => $action->using($deleteSafely));
    }
}
