<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Application;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageRead;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));

        $conversations = Conversation::query()
            ->whereHas('participants', fn ($query) => $query->whereKey($user->id))
            ->with([
                'participants:id,name,role',
                'application.job:id,title',
                'latestMessage.sender:id,name',
                'latestMessage.attachments',
                'latestMessage.reads',
            ])
            ->withCount(['messages as unread_count' => fn ($query) => $query
                ->where('sender_id', '!=', $user->id)
                ->whereDoesntHave('reads', fn ($reads) => $reads->where('user_id', $user->id)->whereNotNull('read_at'))])
            ->when($search !== '', fn ($query) => $query->where(function ($conversations) use ($search) {
                $conversations->whereHas('participants', fn ($participants) => $participants->where('users.name', 'like', "%{$search}%"))
                    ->orWhereHas('application.job', fn ($jobs) => $jobs->where('title', 'like', "%{$search}%"));
            }))
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        return response()->json([
            'current_user_id' => $user->id,
            'conversations' => $conversations->map(fn (Conversation $conversation) => $this->conversationData($conversation, $user)),
        ]);
    }

    public function startForApplication(Request $request, Application $application): JsonResponse
    {
        $application->loadMissing(['job.employer.user', 'jobSeeker.user']);
        $candidate = $application->jobSeeker?->user;
        $employer = $application->job?->employer?->user;
        abort_unless($candidate && $employer, 404);
        abort_unless(in_array($request->user()->id, [$candidate->id, $employer->id], true), 403);

        $conversation = DB::transaction(function () use ($application, $request, $candidate, $employer) {
            $conversation = Conversation::firstOrCreate(
                ['application_id' => $application->id],
                ['created_by' => $request->user()->id],
            );
            $conversation->participants()->syncWithoutDetaching([$candidate->id, $employer->id]);
            return $conversation;
        });

        return response()->json(['conversation' => $this->conversationData(
            $conversation->load(['participants:id,name,role', 'application.job:id,title', 'latestMessage.sender:id,name']),
            $request->user(),
        )], 201);
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        $user = $request->user();
        $messages = $conversation->messages()
            ->with(['sender:id,name', 'attachments', 'reads'])
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'current_user_id' => $user->id,
            'messages' => $messages->map(fn (Message $message) => $this->messageData($message, $user)),
        ]);
    }

    public function storeMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);
        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', File::types(['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'])->max(10240)],
        ]);
        $body = trim((string) ($validated['body'] ?? ''));
        $uploads = $request->file('attachments', []);
        if ($body === '' && count($uploads) === 0) {
            throw ValidationException::withMessages(['body' => 'Write a message or attach a file before sending.']);
        }

        $storedPaths = [];
        try {
            $message = DB::transaction(function () use ($conversation, $request, $body, $uploads, &$storedPaths) {
                $message = $conversation->messages()->create([
                    'sender_id' => $request->user()->id,
                    'body' => $body !== '' ? $body : null,
                ]);

                foreach ($uploads as $upload) {
                    $path = $upload->store("message-attachments/{$conversation->id}", 'local');
                    $storedPaths[] = $path;
                    $message->attachments()->create([
                        'path' => $path,
                        'original_name' => $upload->getClientOriginalName(),
                        'mime_type' => $upload->getMimeType() ?: 'application/octet-stream',
                        'size_bytes' => $upload->getSize() ?: 0,
                    ]);
                }

                $conversation->participants()
                    ->whereKeyNot($request->user()->id)
                    ->get(['users.id'])
                    ->each(fn (User $recipient) => MessageRead::create(['message_id' => $message->id, 'user_id' => $recipient->id]));

                $conversation->touch();
                foreach ($conversation->participants()->whereKeyNot($request->user()->id)->get() as $recipient) {
                    Notification::sendNotification($recipient, 'New message', "{$request->user()->name} sent you a message.", $conversation->id);
                }

                return $message->load(['sender:id,name', 'attachments', 'reads']);
            });
        } catch (\Throwable $exception) {
            if ($storedPaths !== []) Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        DB::afterCommit(fn () => MessageSent::dispatch($message->load(['sender:id,name', 'attachments'])));

        return response()->json(['message' => $this->messageData($message, $request->user())], 201);
    }

    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        MessageRead::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->whereHas('message', fn ($messages) => $messages->where('conversation_id', $conversation->id)->where('sender_id', '!=', $request->user()->id))
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function attachment(Request $request, MessageAttachment $attachment)
    {
        $attachment->loadMissing('message.conversation');
        $this->authorize('view', $attachment->message->conversation);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function conversationData(Conversation $conversation, User $viewer): array
    {
        $latest = $conversation->latestMessage;
        $job = $conversation->application?->job;

        return [
            'id' => $conversation->id,
            'application_id' => $conversation->application_id,
            'job_title' => $job?->title,
            'participants' => $conversation->participants->map(fn (User $participant) => [
                'id' => $participant->id,
                'name' => $participant->name,
                'role' => $participant->role,
            ])->values(),
            'other_participant' => $conversation->participants->firstWhere('id', '!=', $viewer->id) ? [
                'id' => $conversation->participants->firstWhere('id', '!=', $viewer->id)->id,
                'name' => $conversation->participants->firstWhere('id', '!=', $viewer->id)->name,
                'role' => $conversation->participants->firstWhere('id', '!=', $viewer->id)->role,
            ] : null,
            'unread_count' => (int) ($conversation->unread_count ?? 0),
            'updated_at' => $conversation->updated_at?->toIso8601String(),
            'last_message' => $latest ? $this->messageData($latest, $viewer) : null,
        ];
    }

    private function messageData(Message $message, User $viewer): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender?->name,
            'body' => $message->body,
            'created_at' => $message->created_at?->toIso8601String(),
            'is_mine' => $message->sender_id === $viewer->id,
            'is_read' => $message->sender_id === $viewer->id && $message->reads->whereNotNull('read_at')->isNotEmpty(),
            'attachments' => $message->attachments->map(fn (MessageAttachment $attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'size_bytes' => $attachment->size_bytes,
                'download_url' => route('message-attachments.show', $attachment),
            ])->values(),
        ];
    }
}
