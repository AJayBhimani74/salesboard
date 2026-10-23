<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/export/csv', [ReportController::class, 'csv'])->name('reports.csv');
    Route::get('/export/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
});
