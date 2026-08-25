<?php

namespace App\Http\Requests;

use App\Enums\EventLevel;
use Closure;
use DateTimeImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    private const ALLOWED_FIELDS = ['level', 'message', 'source', 'context', 'occurred_at'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('message'))) {
            $this->merge(['message' => trim($this->input('message'))]);
        }

        if ($this->input('source') === '') {
            $this->merge(['source' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'level' => ['required', 'string', Rule::enum(EventLevel::class)],
            'message' => ['required', 'string', 'min:1', 'max:2000'],
            'source' => ['sometimes', 'nullable', 'string', 'max:255'],
            'context' => [
                'sometimes',
                'nullable',
                'array',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    if ($encoded === false || strlen($encoded) > 65536) {
                        $fail('The context field must not exceed 64 KiB when JSON encoded.');
                    }
                },
            ],
            'occurred_at' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)
                        || preg_match('/(?:Z|[+-]\d{2}:\d{2})$/i', $value) !== 1
                        || ! $this->isValidDateTime($value)) {
                        $fail('The occurred_at field must be an ISO 8601 timestamp with a timezone.');
                    }
                },
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $unknownFields = array_diff(array_keys($this->all()), self::ALLOWED_FIELDS);

                foreach ($unknownFields as $field) {
                    $validator->errors()->add($field, "The {$field} field is not allowed.");
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
