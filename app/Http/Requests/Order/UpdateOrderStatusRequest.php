<?php

    namespace App\Http\Requests\Order;

    use Illuminate\Contracts\Validation\ValidationRule;
    use Illuminate\Foundation\Http\FormRequest;
    use Illuminate\Validation\Rule;

    class UpdateOrderStatusRequest extends FormRequest
    {
        public function authorize(): bool
        {
            return true;
        }

        /**
         * @return array<string, ValidationRule|array<mixed>|string>
         */
        public function rules(): array
        {
            return [
                'status' => ['required', 'string', Rule::in(['processing', 'shipped', 'delivered', 'cancelled'])],
            ];
        }
    }
