<?php

namespace App\Http\Requests;

use App\Enums\CalendarEventColor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date_format:Y-m-d'],
            'color' => ['required', Rule::enum(CalendarEventColor::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
