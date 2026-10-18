<?php

    namespace App\Http\Requests\Coupon;

    use Illuminate\Contracts\Validation\ValidationRule;
    use Illuminate\Foundation\Http\FormRequest;
    use Illuminate\Validation\Rule;

    class UpdateCouponRequest extends FormRequest
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
            $coupon = $this->route('coupon');
            $type = $this->input('type', $coupon->type);

            return [
                'code' => [
                    'sometimes', 'string', 'max:100', 'regex:/^[A-Z0-9_-]+$/',
                    Rule::unique('coupons', 'code')->ignore($coupon),
                ],
                'type' => ['sometimes', Rule::in(['percentage', 'fixed'])],
                'value' => [
                    'required_with:type', 'numeric', 'gt:0',
                    Rule::when($type === 'percentage', ['max:100']),
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
