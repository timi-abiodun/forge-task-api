<?php

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Notifications\TaskReminderNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('app:send-task-reminders {--dry-run : Preview without sending notifications}')]
#[Description('Send reminder notifications for tasks due within the next 24 hours.')]
class SendTaskReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = Carbon::now();
        $windowEnd = $now->copy()->addDay();

        $tasks = Task::query()
            ->with(['assignee', 'project', 'assigner'])
            ->whereBetween('due_date', [$now, $windowEnd])
            ->where('status', '!=', TaskStatus::COMPLETED)
            ->whereNotNull('assigned_to')
            ->whereDoesntHave('reminders', function ($query) {
                $query->where('type', 'due_soon');
            })
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('No tasks due for reminders.');
            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($tasks as $task) {

            $isDryRun = $this->option('dry-run');

            foreach ($tasks as $task) {
                if ($isDryRun) {
                    $this->line("[DRY RUN] Would send reminder for task #{$task->id} to {$task->assignee->email}");
                    continue;
                }
            }
            
            try {
                $assigner = $task->assigner;
                $task->assignee->notify(new TaskReminderNotification($task, $assigner));

                $task->reminders()->create([
                    'type' => 'due_soon',
                    'sent_at' => $now,
                ]);

                $this->line("Reminder sent for task #{$task->id} to {$task->assignee->email}");
                $sent++;
            } catch (Throwable $e) {
                $failed++;

                Log::error('Failed to send task reminder', [
                    'task_id' => $task->id,
                    'assigned_to' => $task->assigned_to,
                    'organisation_id' => $task->project->organisation_id,
                    'error' => $e->getMessage(),
                ]);

                $this->error("Failed to send reminder for task #{$task->id}: {$e->getMessage()}");

                // Deliberately do not create a reminder record here.
                // If this task failed, we want the NEXT run to retry it
                // rather than silently skip it forever via whereDoesntHave.
                continue;
            }
        }

        $this->info("Done. Sent: {$sent}, Failed: {$failed}, Total considered: {$tasks->count()}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}