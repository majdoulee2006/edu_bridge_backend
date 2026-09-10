<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class UpdateAccountRequest extends FormRequest
{
    use \App\Traits\NormalizesAccountCredentialsTrait;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeAccountCredentials($this);
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $usr = DB::table('users')->where('user_id', $id)->first();

        $rules = [
            'first_name' => 'nullable|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'full_name'  => 'nullable|string|max:255',
            'phone'      => 'nullable|string|max:20',
            'email'      => [
                'required',
                'email',
                'max:255',
                'unique:users,email,'.$id.',user_id',
            ],
            'password'   => 'nullable|min:6|confirmed',
            'status'     => 'required|in:active,inactive',
        ];

        $roleId = $usr->role_id ?? null;

        if ($roleId == 3) { // Student
            $rules['university_id']    = 'required|string|unique:users,university_id,'.$id.',user_id|max:255';
            $rules['department']       = 'required|string|max:255';
            $rules['program_id']       = 'required|integer|exists:programs,id';
            $rules['level']            = 'required|string|max:255';
            $rules['birth_date']       = 'required|date';
            $rules['gender']           = 'required|in:ذكر,أنثى';
            $rules['telegram_chat_id'] = 'nullable|string|max:100';
        } elseif ($roleId == 2) { // Teacher
            $rules['department']     = 'required|string|max:255';
            $rules['specialization'] = 'required|string|max:255';
            $rules['username']       = ['required', 'string', 'max:255', 'unique:users,username,'.$id.',user_id'];
            $rules['courses']        = 'nullable|array';
        } elseif ($roleId == 5) { // HOD
            $rules['department_id']  = 'required|exists:departments,department_id';
            $rules['username']       = ['required', 'string', 'max:255', 'unique:users,username,'.$id.',user_id'];
        } elseif ($roleId == 6) { // Affairs
            $rules['username']       = ['required', 'string', 'max:255', 'unique:users,username,'.$id.',user_id'];
        } elseif ($roleId == 4) { // Parent
            $rules['username']       = ['nullable', 'string', 'max:255', 'unique:users,username,'.$id.',user_id'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'email.unique'         => 'البريد الإلكتروني مستخدم بالفعل لحساب آخر.',
            'username.unique'      => 'اسم المستخدم مستخدم بالفعل لحساب آخر.',
            'university_id.unique' => 'الرقم الجامعي مستخدم بالفعل لحساب آخر.',
            'password.confirmed'   => 'تأكيد كلمة المرور غير متطابق.',
            'password.min'         => 'يجب ألا تقل كلمة المرور عن 6 أحرف.',
        ];
    }
}
