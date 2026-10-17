<?php

    namespace App\Http\Requests;

    use Illuminate\Contracts\Validation\ValidationRule;
    use Illuminate\Foundation\Http\FormRequest;

    class CheckoutRequest extends FormRequest
    {
        public function authorize(): bool
        {
            return true;
        }

        protected function prepareForValidation(): void
        {
            // Surface the header as input so it goes through normal validation.
            $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
        }

        /**
         * @return array<string, ValidationRule|array<mixed>|string>
         */
        public function rules(): array
        {
            return [
                'shipping_name' => ['required', 'string', 'max:255'],
                'shipping_address' => ['required', 'string', 'max:500'],
                'shipping_city' => ['required', 'string', 'max:100'],
                'shipping_country' => ['required', 'string', 'max:100'],
                'shipping_postal_code' => ['nullable', 'string', 'max:20'],
                'coupon_code' => ['nullable', 'string', 'max:100'],
                'idempotency_key' => ['nullable', 'string', 'max:100'],
            ];
        }
    }
