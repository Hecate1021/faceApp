<?php

use App\Http\Controllers\EnrollController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::get('/enroll', [EnrollController::class, 'create'])->name('enroll.create');
Route::post('/enroll', [EnrollController::class, 'store'])->name('enroll.store');

Route::get('/verify', [EnrollController::class, 'verify'])->name('enroll.verify');
Route::get('/api/enrolled-faces', [EnrollController::class, 'enrolledFaces']);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
