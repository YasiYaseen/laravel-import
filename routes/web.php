<?php

use App\Http\Controllers\CustomerImportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    Route::get('/import', [CustomerImportController::class, 'showForm']);
    Route::post('/import/spatie', [CustomerImportController::class, 'importSpatie']);
    Route::post('/import/laravel-excel', [CustomerImportController::class, 'importLaravelExcel']);
    Route::post('/import/fast-excel', [CustomerImportController::class, 'importFastExcel']);
    Route::post('/import/openspout', [CustomerImportController::class, 'importOpenSpout']);
    // Row Count Routes
    Route::post('/count-rows/spatie', [CustomerImportController::class, 'countRowsSpatie']);
    Route::post('/count-rows/laravel-excel', [CustomerImportController::class, 'countRowsLaravelExcel']);
    Route::post('/count-rows/fast-excel', [CustomerImportController::class, 'countRowsFastExcel']);
    Route::post('/count-rows/openspout', [CustomerImportController::class, 'countRowsOpenSpout']);

});

require __DIR__.'/auth.php';
