<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOsaForm3Request extends FormRequest
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
            'program_name' => ['required', 'string', 'max:255'],
            'course' => ['required', 'string', 'max:255'],
            'destination_venue' => ['required', 'string', 'max:255'],
            'inclusive_dates' => ['required', 'string', 'max:255'],
            'number_of_students' => ['required', 'integer', 'min:1'],
            'personnel_in_charge' => ['required', 'string'],
            'compliance' => ['required', 'array', 'size:11'],
            'compliance.*.compliance' => ['required', 'boolean'],
            'compliance.*.remarks' => ['nullable', 'string', 'max:255'],
        ];
    }
}
