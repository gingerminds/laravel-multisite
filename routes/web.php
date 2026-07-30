<?php

declare(strict_types=1);

use Gingerminds\LaravelMultisite\Http\Controllers\Translation\TranslationController;
use Gingerminds\LaravelMultisite\Resolver\ResourceResolver;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'gingerminds-core.auth'])
    ->prefix(config('gingerminds-core.admin_prefix'))
    ->name('gingerminds-multisite.')
    ->group(function () {
        Route::resource('sites', ResourceResolver::controller('site'));
        Route::resource('languages', ResourceResolver::controller('language'));
        Route::post('translations/refresh', [TranslationController::class, 'refresh'])
            ->name('translations.refresh');
    });
