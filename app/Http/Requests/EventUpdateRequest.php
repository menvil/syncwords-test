<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

class EventUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        try {
            $event = Event::findOrFail($this->segment(2));
        } catch (ModelNotFoundException $e) {
            $event = null;
        }
        $rules = [];
        if(is_null($event)) {
            return $rules;
        }

        $rules = [
            'event_title' => [
                'min:1',
                'max:200'
            ]
        ];

        if(request()->exists('event_start_date') && !request()->exists('event_end_date')) {
            $time = Carbon::parse($event->event_end_date);
            $rules['event_start_date'] = [
                'date',
                'date_format:Y-m-d H:i:s',
                'before_or_equal:' . $time->format('Y-m-d H:i:s'),
                'after_or_equal:' . $time->subHours(12)->format('Y-m-d H:i:s')
            ];
        } else if (!request()->exists('event_start_date') && request()->exists('event_end_date')) {
            $time = Carbon::parse($event->event_start_date);
            $rules['event_end_date'] = [
                'date',
                'date_format:Y-m-d H:i:s',
                'after_or_equal:' . $time->format('Y-m-d H:i:s'),
                'before_or_equal:' . $time->addHours(12)->format('Y-m-d H:i:s')
            ];
        } else if(request()->exists('event_start_date') && request()->exists('event_end_date')) {

            try {
                $maxTime = Carbon::parse($this->all()['event_start_date']);
            } catch (InvalidFormatException $e) {
                $maxTime = null;
            }

            $rules['event_start_date'] = [
                'date',
                'date_format:Y-m-d H:i:s'
            ];
            $rules['event_end_date'] = [
                'date',
                'date_format:Y-m-d H:i:s',
                'after:event_start_date',
                'before_or_equal:' . $maxTime->addHours(12)->format('Y-m-d H:i:s')
            ];
        }

        return $rules;
    }
}
