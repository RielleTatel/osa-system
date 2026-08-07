<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'activity_type' => ['required', 'in:in_campus,off_campus'],
            'title' => ['required', 'string', 'max:255'],
            'nature_of_activity' => ['required', 'string', 'max:255'],
            'nature_of_engagement' => ['required', 'in:organizer,partner,participant'],
            'main_organizer' => ['nullable', 'string', 'max:255', 'required_unless:nature_of_engagement,organizer'],
            'date_start' => ['required', 'date', 'after_or_equal:'.now()->addDays(3)->toDateString()],
            'date_end' => ['required', 'date', 'after_or_equal:date_start'],
            'time_of_activity' => ['required', 'date_format:H:i'],
            'venue' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string'],
            'participants' => ['array'],
            'participants.*.full_name' => ['required', 'string', 'max:255'],
            'participants.*.year_course' => ['nullable', 'string', 'max:255'],
            'participants.*.contact_info' => ['nullable', 'string', 'max:255'],
            'schedule_items' => ['array'],
            'schedule_items.*.time_slot' => ['required', 'string', 'max:255'],
            'schedule_items.*.description' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_start.after_or_equal' => 'Requests must be filed at least 3 days before the activity date.',
        ];
    }
}
