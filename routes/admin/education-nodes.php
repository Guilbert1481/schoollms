<?php

use App\Http\Controllers\Admin\EducationNodeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:admin,superadmin'])
    ->prefix('admin/education-nodes')
    ->name('admin.education-nodes.')
    ->group(function () {
        Route::get('/',                 [EducationNodeController::class, 'index'])->name('index');
        Route::post('/',                [EducationNodeController::class, 'store'])->name('store');
        Route::put('/{education_node}', [EducationNodeController::class, 'update'])->name('update');
        Route::patch('/{education_node}', [EducationNodeController::class, 'update']);
        Route::delete('/{education_node}', [EducationNodeController::class, 'destroy'])->name('destroy');
        Route::post('/{education_node}/toggle-offered', [EducationNodeController::class, 'toggleOffered'])
            ->name('toggle-offered');
    });
