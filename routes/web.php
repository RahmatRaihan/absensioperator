<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PeriodeController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\BulkUploadController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\Auth\LoginController;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'authenticate']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('periode', PeriodeController::class)->except(['create', 'edit', 'show']);

    Route::resource('operator', OperatorController::class)->except(['create', 'edit', 'show']);
    Route::post('operator/{operator}/toggle-status', [OperatorController::class, 'toggleStatus'])->name('operator.toggle-status');

    Route::get('absensi/entry', [AbsensiController::class, 'entry'])->name('absensi.entry');
    Route::post('absensi/entry', [AbsensiController::class, 'store'])->name('absensi.store');
    Route::post('absensi/entry/autosave', [AbsensiController::class, 'autoSave'])->name('absensi.autosave');

    Route::get('absensi/upload', [BulkUploadController::class, 'index'])->name('absensi.upload');
    Route::get('absensi/upload/template', [BulkUploadController::class, 'downloadTemplate'])->name('absensi.upload.template');
    Route::post('absensi/upload', [BulkUploadController::class, 'upload'])->name('absensi.upload.process');
    Route::post('absensi/upload/confirm', [BulkUploadController::class, 'confirm'])->name('absensi.upload.confirm');

    Route::get('rekap', [RekapController::class, 'index'])->name('rekap.index');

    Route::get('export/excel', [ExportController::class, 'excel'])->name('export.excel');
    Route::get('export/pdf', [ExportController::class, 'pdf'])->name('export.pdf');
    Route::get('export/print', [ExportController::class, 'printView'])->name('export.print');
});

