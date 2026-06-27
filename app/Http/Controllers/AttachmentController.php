<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Task;
use App\Models\Attachment;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    /**
     * Display a paginated listing of the organization's attachments.
     */
    public function index(Task $task): JsonResponse
    {
        $this->authorize('viewAny', [Attachment::class, $task]);
        return response()->json($task->attachments()->paginate(15), Response::HTTP_OK);
    }

    /**
     * Store a newly created Attachment in storage.
     */
    public function store(StoreAttachmentRequest $request, Task $task): JsonResponse
    {
        $this->authorize('create', [Attachment::class, $task]);

        // Validate and retrieve the file
        $file = $request->file('attachment');

        // Store the file physically
        // Never trust client-provided filenames; store with a unique hash
        $path = $file->store('attachments', config('attachments.disk'));

        // Assemble metadata manually
        $attachment = $task->attachments()->create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_disk' => config('attachments.disk'),
            // Use server-detected MIME type (do not trust client-provided Content-Type)
            'mime_type' => $file->getMimeType(),

            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json($attachment, Response::HTTP_CREATED);
    }

    /**
     * Display the specified Attachment.
     */
    public function show(Task $task, Attachment $attachment): JsonResponse
    {
        // Ensure the attachment belongs to the specified task
        abort_if($attachment->task_id !== $task->id, 404);

        // Instantly checks the 'view' method in the ProjectPolicy
        $this->authorize("view", $attachment);

        return response()->json($attachment, Response::HTTP_OK);
    }

    /**
     * Download the specified Attachment.
     */
    public function download(Task $task, Attachment $attachment): Response
    {
        // Ensure the attachment belongs to the specified task
        abort_if($attachment->task_id !== $task->id, 404);

        // Re-verify authorization to ensure the user can access this specific file
        $this->authorize("view", $attachment);

        $diskName = $attachment->file_disk;
        $disk = Storage::disk($diskName);

        if (!$disk->exists($attachment->file_path)) {
            abort(404, "File not found on server.");
        }

        $driver = config("filesystems.disks.{$diskName}.driver");

        // Local filesystem drivers can be streamed via absolute path
        if ($driver === 'local') {
            return response()->download(
                $disk->path($attachment->file_path),
                $attachment->file_name
            );
        }

        // Remote drivers (e.g. s3): redirect via signed temporary URL
        if (method_exists($disk, 'temporaryUrl')) {
            $url = $disk->temporaryUrl($attachment->file_path, now()->addMinutes(15));
            return redirect()->away($url);
        }

        abort(404, 'Temporary download URL not supported for this storage driver.');

    }


    /**
     * Remove the specified Attachment from storage.
     */
    public function destroy(Task $task, Attachment $attachment): JsonResponse
    {
        // Ensure the attachment belongs to the specified task
        abort_if($attachment->task_id !== $task->id, 404);

        // Instantly checks the 'delete' method in the ProjectPolicy
        $this->authorize("delete", $attachment);

        Storage::disk($attachment->file_disk)->delete($attachment->file_path);

        $attachment->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}