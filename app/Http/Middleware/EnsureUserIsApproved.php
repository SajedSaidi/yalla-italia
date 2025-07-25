<?php
// filepath: c:\Users\sajed\OneDrive\Desktop\yalla-italia\app\Http\Middleware\EnsureUserIsApproved.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::user()->isStudent() && !Auth::user()->isApproved()) {
            Auth::logout();

            return redirect()->route('filament.admin.auth.login')
                ->with('error', 'Your account is pending approval. Please wait for admin confirmation.');
        }

        return $next($request);
    }
}
