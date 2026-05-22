<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Traits\ChecksOrganisationRole;

class UpdateTaskRequest extends FormRequest
{
    use ChecksOrganisationRole;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Pulls the '{task}' model binding directly from the URL route
        $task = $this->route('task'); 

        return $this->user()->can('update', $task);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $task = $this->route('task');
        
        $isAdmin = $this->hasAdministrativeAccess();

       // Check task relationship boundaries
        $isAssigner = $task->assigned_by === $user->id;
        $isAssignee = $task->assigned_to === $user->id;

        // Define restricted status: You are locked down ONLY if you are the assignee,
        // but you do NOT have assigner permissions AND you do NOT have admin permissions.
        $shouldRestrictFields = $isAssignee && !$isAssigner && !$isAdmin;

        // If they are just the assignee, restrict their input to 'status' only
        if ($shouldRestrictFields) {
            return [
                'status' => ['sometimes', Rule::enum(TaskStatus::class)],
                'name' => ['prohibited'],
                'description' => ['prohibited'],
                'due_date' => ['prohibited'],
            ];
        }

        // Otherwise, they are an Admin or the Creator: full access rules
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'due_date' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:today']
        ];
    }
}
