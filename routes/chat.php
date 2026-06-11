<?php

use Illuminate\Support\Facades\Route;
use UnseenCodes\Chat\Http\Controllers\AttachmentController;

Route::group([
    'prefix'     => config('chat.routes.prefix', 'chat'),
    'middleware' => config('chat.routes.middleware', ['web', 'auth']),
], function () {
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('chat.attachment.download');
});
