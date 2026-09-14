<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('products', 'slug')->ignore($this->product)],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($this->product) ],
            'price' => ['required', 'numeric'],
            'status' => ['required',Rule::in(['active', 'inactive', 'draft'])],
            'discount_price' => ['nullable', 'numeric', 'lt:price'],
            'category_ids' => ['nullable','array'],
            'category_ids.*' => ['exists:categories,id'],
        ];
    }
}
