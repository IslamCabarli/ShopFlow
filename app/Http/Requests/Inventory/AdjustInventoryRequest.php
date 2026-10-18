<?php

    namespace App\Http\Requests\Inventory;

    use Illuminate\Contracts\Validation\ValidationRule;
    use Illuminate\Foundation\Http\FormRequest;

    class AdjustInventoryRequest extends FormRequest
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
                'delta' => ['required', 'integer', 'not_in:0', 'between:-100000,100000'],
            ];
        }
    }
