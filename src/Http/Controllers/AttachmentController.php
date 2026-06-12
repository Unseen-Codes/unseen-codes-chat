<?php

namespace UnseenCodes\Chat\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use UnseenCodes\Chat\Contracts\ChatManagerContract;

class AttachmentController extends Controller
{
    public function download(Request $request, $id)
    {
        $attachment = config('chat.models.attachment')::with('message.conversation')
            ->findOrFail($id);

        abort_unless(
            app(ChatManagerContract::class)->isParticipant(
                $request->user(),
                $attachment->message->conversation
            ),
            403
        );

        return Storage::disk($attachment->disk)
            ->download($attachment->path, $attachment->original_name);
    }
}
