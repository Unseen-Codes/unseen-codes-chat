<?php

use Illuminate\Support\Facades\Route;
use UnseenCodes\Chat\Http\Controllers\AttachmentController;

Route::middleware(config('chat.routes.middleware', ['web', 'auth']))
    ->prefix(config('chat.routes.prefix', 'chat'))
    ->group(function () {
        Route::get('/attachments/{id}/download', [AttachmentController::class, 'download'])
            ->name('chat.attachment.download');
    });
