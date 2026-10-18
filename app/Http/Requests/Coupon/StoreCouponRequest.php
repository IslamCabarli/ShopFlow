<?php

    namespace App\Http\Requests\Coupon;

    use Illuminate\Contracts\Validation\ValidationRule;
    use Illuminate\Foundation\Http\FormRequest;
    use Illuminate\Validation\Rule;

    class StoreCouponRequest extends FormRequest
    {
        public function authorize(): bool
        {
            return true;
        }

        protected function prepareForValidation(): void
        {
            if ($this->has('code')) {
                $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
            }
        }

        /**
         * @return array<string, ValidationRule|array<mixed>|string>
         */
        public function rules(): array
        {
            return [
                'code' => ['required', 'string', 'max:100', 'regex:/^[A-Z0-9_-]+$/', 'unique:coupons,code'],
                'type' => ['required', Rule::in(['percentage', 'fixed'])],
                'value' => [
                    'required', 'numeric', 'gt:0',
                    Rule::when($this->input('type') === 'percentage', ['max:100']),
                ],
                'minimum_order_amount' => ['sometimes', 'numeric', 'min:0'],
                'maximum_discount' => ['nullable', 'numeric', 'gt:0'],
                'usage_limit' => ['nullable', 'integer', 'min:1'],
                'per_user_limit' => ['nullable', 'integer', 'min:1'],
                'starts_at' => ['nullable', 'date'],
                'expires_at' => [
                    'nullable', 'date',
                    Rule::when($this->filled('starts_at'), ['after:starts_at']),
                ],
                'is_active' => ['sometimes', 'boolean'],
            ];
        }
    }
