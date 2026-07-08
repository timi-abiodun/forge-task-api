<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Notifications\TaskAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class SendTaskCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TaskCreated $event): void
    {
        // Ensure related models are available for notification payloads.
        $event->task->loadMissing(['assignee', 'project']);

        // Always notify the creator that the task was created.
        // Idempotency guard: if this exact notification was already sent recently,
        // Cache::add will fail and we skip sending a duplicate.
        if ($this->shouldSend($event->task->id, $event->assigner->id, $event->assigner->id, 'task-created')) {
            $event->assigner->notify(new TaskAssigned($event->task, $event->assigner));
        }

        // Notify the assignee too, but skip duplicate self-notifications.
        if ($event->task->assignee && $event->task->assignee->id !== $event->assigner->id) {
            if ($this->shouldSend($event->task->id, $event->task->assignee->id, $event->assigner->id, 'task-created')) {
                $event->task->assignee->notify(new TaskAssigned($event->task, $event->assigner));
            }
        }
    }

    private function shouldSend(string $taskId, string $recipientId, string $assignerId, string $context): bool
    {
        // Include context + task + recipient + assigner so each unique
        // notification intent has its own dedupe key.
        $key = sprintf('notification:%s:%s:%s:%s', $context, $taskId, $recipientId, $assignerId);

        // Cache::add is atomic: true only when key did not exist.
        // TTL keeps key temporary so legitimate future notifications still send.
        return Cache::add($key, true, now()->addMinutes(5));
    }
}
