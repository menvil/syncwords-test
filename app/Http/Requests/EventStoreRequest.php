<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

class EventStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        try {
            $maxTime = Carbon::parse($this->all()['event_start_date']);
        } catch (InvalidFormatException $e) {
            $maxTime = null;
        }

        return [
            'event_title' => ['required', 'min:1', 'max:200'],
            'event_start_date' => ['required', 'date', 'date_format:Y-m-d H:i:s'],
            'event_end_date' => ['required', 'date', 'date_format:Y-m-d H:i:s', 'after:event_start_date', 'before:' . $maxTime->addHours(12)->format('Y-m-d H:i:s')],
        ];
    }
}
