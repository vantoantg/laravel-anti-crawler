<?php

use Illuminate\Support\Facades\Route;
use VanToanTG\AntiCrawler\Http\Controllers\BotProtectionController;

Route::prefix('admin/bot-protection')
    ->name('admin.bot-protection.')
    ->middleware(['web', 'auth'])
    ->group(function () {
        Route::get('/', [BotProtectionController::class, 'index'])->name('index');
        Route::get('/logs', [BotProtectionController::class, 'logs'])->name('logs');
        Route::get('/blocked-ips', [BotProtectionController::class, 'blockedIps'])->name('blocked-ips');
        Route::post('/block-ip', [BotProtectionController::class, 'blockIp'])->name('block-ip');
        Route::delete('/unblock-ip/{id}', [BotProtectionController::class, 'unblockIp'])->name('unblock-ip');
        Route::get('/whitelist', [BotProtectionController::class, 'whitelist'])->name('whitelist');
        Route::post('/whitelist', [BotProtectionController::class, 'addToWhitelist'])->name('whitelist.add');
        Route::delete('/whitelist/{id}', [BotProtectionController::class, 'removeFromWhitelist'])->name('whitelist.remove');
        Route::get('/stats', [BotProtectionController::class, 'stats'])->name('stats');
    });
