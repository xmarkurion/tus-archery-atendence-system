<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\RegController;
use App\Http\Controllers\MeetingSettingsController;
use App\Http\Controllers\MeetingRegistrationController;


Route::get('/', function () {
    return Inertia::render('Welcome', [
//        'canRegister' => Features::enabled(Features::registration()),
        'canRegister' => false,
    ]);
})->name('home');

// Custom quick registration endpoint disabled to turn off registrations.
 Route::post('/register-number', [RegController::class, 'store'])->name('regs.store');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/meeting-settings', [MeetingSettingsController::class, 'index'])->name('meeting.settings.index');
    Route::post('/meeting-settings', [MeetingSettingsController::class, 'update'])->name('meeting.settings.update');
    Route::post('/meeting-settings/toggle', [MeetingSettingsController::class, 'toggle'])->name('meeting.settings.toggle');
    Route::post('/meeting-settings/run-now', [MeetingSettingsController::class, 'runNow'])->name('meeting.settings.runnow');
    Route::get('/meeting/register', [MeetingRegistrationController::class, 'showToday'])->name('meeting.register');
    Route::post('/meeting/register', [MeetingRegistrationController::class, 'register'])->name('meeting.register.post');
    // Meeting management (details, update, remove attendee)
    Route::get('/meeting/{id}/details', [MeetingSettingsController::class, 'meetingDetails'])->name('meeting.details');
    Route::post('/meeting/{id}/update', [MeetingSettingsController::class, 'updateMeeting'])->name('meeting.update');
    Route::post('/meeting/{meetingId}/remove-attendee/{regId}', [MeetingSettingsController::class, 'removeAttendee'])->name('meeting.removeAttendee');
    // Export meeting to PDF / printable HTML
    Route::get('/meeting/{id}/export', [MeetingSettingsController::class, 'exportPdf'])->name('meeting.export');
    // View meeting as a standalone Inertia page (instead of modal)
    Route::get('/meeting/{id}/view', [MeetingSettingsController::class, 'showMeeting'])->name('meeting.view');
    // Reg management (admin): list, update, delete regs
    Route::get('/reg-settings', [RegController::class, 'index'])->name('regs.index');
    Route::post('/regs/{reg}/update', [RegController::class, 'update'])->name('regs.update');
    Route::post('/regs/{reg}/delete', [RegController::class, 'destroy'])->name('regs.destroy');
    // Allow deleting via browser GET for quick access or POST for AJAX/forms
    Route::match(['get','post'], '/meeting/{id}/delete', [MeetingSettingsController::class, 'destroyMeeting'])->name('meeting.delete');

});

// Public API endpoints for meeting registration
Route::get('/api/meeting/today', [MeetingRegistrationController::class, 'apiToday']);
Route::post('/api/meeting/verify-pin', [MeetingRegistrationController::class, 'apiVerifyPin']);
Route::post('/api/meeting/register', [MeetingRegistrationController::class, 'apiRegister']);
// Allow visiting the endpoint directly (GET) or calling it programmatically (POST)
Route::match(['get', 'post'], '/api/meeting/create', [MeetingRegistrationController::class, 'apiCreateTodayWithPin']);

require __DIR__.'/settings.php';
