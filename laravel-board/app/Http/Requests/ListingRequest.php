<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $price = $this->input('price');

        if (is_string($price)) {
            $price = str_replace(',', '.', $price);
        }

        $category = $this->input('category_id');

        $this->merge([
            'price' => $price,
            'category_id' => $category === null || $category === '' ? null : $category,
        ]);
    }
}
