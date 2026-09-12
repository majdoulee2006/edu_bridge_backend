<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherAccountRequest extends FormRequest
{
    use \App\Traits\NormalizesAccountCredentialsTrait;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeAccountCredentials($this);

        $fullName = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
        if (empty($fullName)) {
            $fullName = $this->full_name ?? '';
        }

        $specialization = $this->advisor_branch ?? 'عام';

        $this->merge([
            'full_name'      => $fullName,
            'specialization' => $specialization,
        ]);
    }

    public function rules(): array
    {
        return [
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'username'       => [
                'required',
                'string',
                'unique:users,username',
                'max:255',
            ],
            'phone'          => 'nullable|string|max:20',
            'email'          => [
                'required',
                'email',
                'unique:users,email',
                'max:255',
            ],
            'department'     => 'required|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'password'       => 'required|string|min:6|confirmed',
            'courses'        => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'الاسم الأول مطلوب.',
            'last_name.required'  => 'الاسم الثاني مطلوب.',
            'username.unique'     => 'اسم المستخدم مستخدم بالفعل.',
            'email.unique'        => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed'  => 'تأكيد كلمة المرور غير متطابق.',
        ];
    }
}
