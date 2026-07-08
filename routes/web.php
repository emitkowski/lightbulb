<?php

use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Route;

Route::get('/team-invitation/{token}', function (string $token) {
    $invitation = TeamInvitation::where('token', $token)->firstOrFail();

    return inertia('Teams/AcceptInvitation', ['token' => $token, 'team' => $invitation->team->name]);
})->name('team.invitation');

Route::post('/team-invitation/{token}/accept', function (string $token) {
    $invitation = TeamInvitation::where('token', $token)
        ->where('email', auth()->user()->email)
        ->firstOrFail();

    if ($invitation->isPending()) {
        $invitation->team->members()->syncWithoutDetaching([auth()->id()]);
        $invitation->update(['accepted_at' => now()]);
    }

    return redirect('/dashboard');
})->middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
  ->name('team.invitation.accept');

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return inertia('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => app()->version(),
        'phpVersion'     => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', fn () => inertia()->location('/admin'))->name('dashboard');
});
