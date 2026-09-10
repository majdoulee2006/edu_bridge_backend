<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentAccountRequest extends FormRequest
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
        $this->merge(['full_name' => $fullName]);
    }

    public function rules(): array
    {
        return [
            'first_name'       => 'required|string|max:100',
            'last_name'        => 'required|string|max:100',
            'university_id'    => 'required|string|unique:users,university_id|max:255',
            'email'            => [
                'required',
                'email',
                'unique:users,email',
                'max:255',
            ],
            'phone'            => 'nullable|string|max:20',
            'telegram_chat_id' => 'nullable|string|max:100',
            'department'       => 'required|string|max:255',
            'program_id'       => 'required|integer|exists:programs,id',
            'level'            => 'required|string|max:255',
            'birth_date'       => 'required|date',
            'gender'           => 'required|in:ذكر,أنثى',
            'password'         => 'required|string|min:6|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required'  => 'الاسم الأول مطلوب.',
            'last_name.required'   => 'الاسم الثاني مطلوب.',
            'university_id.unique' => 'الرقم الجامعي مستخدم بالفعل لحساب آخر.',
            'email.unique'         => 'البريد الإلكتروني مستخدم بالفعل لحساب آخر.',
            'password.confirmed'   => 'تأكيد كلمة المرور غير متطابق.',
        ];
    }
}
