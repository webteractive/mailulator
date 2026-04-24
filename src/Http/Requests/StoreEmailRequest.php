<?php

namespace Webteractive\Mailulator\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'from' => ['required', 'string', 'max:255'],
            'to' => ['required'],
            'to.*' => ['string', 'max:255'],
            'cc' => ['sometimes', 'nullable'],
            'cc.*' => ['string', 'max:255'],
            'bcc' => ['sometimes', 'nullable'],
            'bcc.*' => ['string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:998'],
            'html_body' => ['nullable', 'string'],
            'text_body' => ['nullable', 'string'],
        ];

        if ($this->isJson()) {
            $rules += [
                'headers' => ['nullable', 'array'],
                'attachments' => ['sometimes', 'array'],
                'attachments.*.filename' => ['required', 'string'],
                'attachments.*.mime_type' => ['required', 'string'],
                'attachments.*.content' => ['required', 'string'],
            ];
        } else {
            $rules += [
                'headers' => ['nullable', 'string'],
                'attachments' => ['sometimes', 'array'],
                'attachments.*' => ['file'],
            ];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'to' => $this->normaliseList($this->input('to')),
            'cc' => $this->normaliseList($this->input('cc')),
            'bcc' => $this->normaliseList($this->input('bcc')),
        ]);
    }

    protected function normaliseList($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($v) => $v !== null && $v !== ''));
        }

        if (is_string($value) && $value !== '') {
            return [$value];
        }

        return [];
    }

    public function parsedHeaders(): array
    {
        $headers = $this->input('headers');

        if (is_array($headers)) {
            return $headers;
        }

        if (is_string($headers) && $headers !== '') {
            $decoded = json_decode($headers, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
