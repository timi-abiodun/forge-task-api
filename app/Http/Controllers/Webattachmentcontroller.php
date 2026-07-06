<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Models\Task;
use Illuminate\Support\Facades\Storage;

class WebAttachmentController extends Controller
{
    public function store(StoreAttachmentRequest $request, Task $task)
    {
        $this->authorize('create', [Attachment::class, $task]);

        $file = $request->file('attachment');
        $path = $file->store('attachments', config('attachments.disk'));

        $task->attachments()->create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_disk' => config('attachments.disk'),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->route('tasks.show', $task)->with('status', 'File uploaded.');
    }

    public function download(Task $task, Attachment $attachment)
    {
        abort_if($attachment->task_id !== $task->id, 404);
        $this->authorize('view', $attachment);

        $disk = Storage::disk($attachment->file_disk);

        if (!$disk->exists($attachment->file_path)) {
            abort(404, 'File not found on server.');
        }

        $driver = config("filesystems.disks.{$attachment->file_disk}.driver");

        if ($driver === 'local') {
            return response()->download($disk->path($attachment->file_path), $attachment->file_name);
        }

        if (method_exists($disk, 'temporaryUrl')) {
            return redirect()->away($disk->temporaryUrl($attachment->file_path, now()->addMinutes(15)));
        }

        abort(404, 'Temporary download URL not supported for this storage driver.');
    }

    public function destroy(Task $task, Attachment $attachment)
    {
        abort_if($attachment->task_id !== $task->id, 404);
        $this->authorize('delete', $attachment);

        Storage::disk($attachment->file_disk)->delete($attachment->file_path);
        $attachment->delete();

        return redirect()->route('tasks.show', $task)->with('status', 'Attachment deleted.');
    }
}