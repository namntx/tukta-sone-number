<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $customerId = $this->route('customer')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('customers', 'phone')->ignore($customerId)
            ],
            'is_active' => ['nullable', 'boolean'],
            'rates' => ['nullable', 'array'],
            'rates.*' => ['nullable', 'array'], // rates[bac], rates[trung], rates[nam]
            'rates.*.*' => ['nullable', 'array'], // rates[bac][bao_lo_2]
            'rates.*.*.commission' => ['nullable', 'numeric', 'min:0'],
            'rates.*.*.payout_times' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên khách hàng.',
            'name.max' => 'Tên khách hàng không được vượt quá 255 ký tự.',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
            'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự.',
        ];
    }
}

