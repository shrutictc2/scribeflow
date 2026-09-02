<?php

use App\Http\Controllers\RecordingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/recordings');

Route::controller(RecordingController::class)->prefix('recordings')->name('recordings.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/new', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{recording}', 'show')->name('show');
    Route::get('/{recording}/status', 'status')->name('status');
    Route::delete('/{recording}', 'destroy')->name('destroy');
});
