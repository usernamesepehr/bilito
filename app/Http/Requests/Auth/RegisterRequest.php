<?php

namespace App\Http\Requests\Auth;

use App\Support\NationalId;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => PhoneNormalizer::normalize($this->input('phone')),
            'national_id' => NationalId::normalize($this->input('national_id')),
        ]);
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/', 'unique:users,phone'],
            'firstname' => ['required', 'string', 'min:2', 'max:100'],
            'lastname' => ['required', 'string', 'min:2', 'max:100'],
            'gender' => ['required', 'in:male,female'],
            'national_id' => [
                'required',
                'string',
                'size:10',
                'unique:users,national_id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! NationalId::validate($value)) {
                        $fail('The national id is not valid.');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
