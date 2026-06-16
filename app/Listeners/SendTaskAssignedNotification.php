<?php

namespace App\Listeners;

use App\Events\TaskReassigned;
use App\Notifications\TaskAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class SendTaskAssignedNotification implements ShouldQueue
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
    public function handle(TaskReassigned $event): void
    {
        // Ensure assignee/project are loaded for message content.
        $event->task->loadMissing(['assignee', 'project']);

        // Nothing to notify if the task has no assignee.
        if (!$event->task->assignee) {
            return;
        }

        // Avoid notifying when someone assigns a task to themselves.
        if ($event->task->assignee->id !== $event->assigner->id) {
            // Idempotency guard: skip duplicate sends for the same recipient/task/assigner
            // when the same reassignment event is dispatched repeatedly.
            if ($this->shouldSend($event->task->id, $event->task->assignee->id, $event->assigner->id, 'task-reassigned')) {
                $event->task->assignee->notify(new TaskAssigned($event->task, $event->assigner));
            }
        }
    }

    private function shouldSend(string $taskId, string $recipientId, string $assignerId, string $context): bool
    {
        // Key dimensions represent one unique notification intent.
        $key = sprintf('notification:%s:%s:%s:%s', $context, $taskId, $recipientId, $assignerId);

        // Atomic add + short TTL gives lightweight dedupe without permanent locks.
        return Cache::add($key, true, now()->addMinutes(5));
    }
}
