<?php

namespace App\Http\Requests;

use App\Enums\ActivityProgress;
use App\Enums\ActivityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrackerFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($this->filled('from') ? ['after_or_equal:from'] : [])],
            'progress' => ['nullable', Rule::enum(ActivityProgress::class)],
            'approval' => ['nullable', Rule::enum(ActivityStatus::class)],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'activity_type' => ['nullable', Rule::in(['in_campus', 'off_campus'])],
        ];
    }
}
