<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\ProjectStatus;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
       // Pulls the '{project}' model binding directly from the URL route
        $project = $this->route('project'); 

        return $this->user()->can('update', $project);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 'sometimes' means: validate this rule ONLY if the key is present in the payload
            'name' => ['sometimes', 'required', 'string', 'max:255'], 
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::enum(ProjectStatus::class)],
        ];
    }
}
