<?php

namespace App\Http\Requests;

use App\Models\Attachment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attachment' => [
                'required',
                'file',
                'max:10240', // 10MB, in kilobytes
                'mimes:pdf,doc,docx,xls,xlsx,csv,png,jpg,jpeg,gif,zip,txt',
            ]
        ];
    }
}
