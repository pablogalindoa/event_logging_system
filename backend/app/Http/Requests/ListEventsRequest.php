<?php

namespace App\Http\Requests;

use App\Enums\EventLevel;
use Closure;
use DateTimeImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ListEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'level' => ['sometimes', 'string', Rule::enum(EventLevel::class)],
            'from' => ['sometimes', 'string', $this->iso8601WithTimezoneRule()],
            'to' => ['sometimes', 'string', $this->iso8601WithTimezoneRule()],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['from', 'to'])
                    || ! $this->filled('from')
                    || ! $this->filled('to')) {
                    return;
                }

                if (new DateTimeImmutable($this->string('from')->toString()) > new DateTimeImmutable($this->string('to')->toString())) {
                    $validator->errors()->add('to', 'The to field must be after or equal to from.');
                }
            },
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'error' => [
                'code' => 'VALIDATION_FAILED',
                'message' => 'The request contains invalid data.',
                'details' => $validator->errors()->toArray(),
                'request_id' => $this->attributes->get('request_id'),
            ],
        ], 422));
    }

    private function iso8601WithTimezoneRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value)
                || preg_match('/(?:Z|[+-]\d{2}:\d{2})$/i', $value) !== 1
                || ! $this->isValidDateTime($value)) {
                $fail("The {$attribute} field must be an ISO 8601 timestamp with a timezone.");
            }
        };
    }

    private function isValidDateTime(string $value): bool
    {
        try {
            new DateTimeImmutable($value);

            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
