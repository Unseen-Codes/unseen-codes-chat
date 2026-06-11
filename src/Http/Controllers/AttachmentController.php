<?php

namespace UnseenCodes\Chat\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use UnseenCodes\Chat\Contracts\ChatManagerContract;

class AttachmentController extends Controller
{
    public function download(Request $request, $attachmentId)
    {
        $attachment = config('chat.models.attachment')::with('message.conversation')
            ->findOrFail($attachmentId);

        // Ensure user is a participant in the conversation
        abort_unless(
            app(ChatManagerContract::class)->isParticipant(
                $request->user(),
                $attachment->message->conversation
            ),
            403
        );

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name
        );
    }
}
