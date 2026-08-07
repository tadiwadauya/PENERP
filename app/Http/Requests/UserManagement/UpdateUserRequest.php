<?php

namespace App\Http\Requests\UserManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'user-management.users.update'
        ) ?? false;
    }

    public function rules(): array
    {
        $managedUser = $this->route('user');

        return [
            'employee_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique(
                    'users',
                    'employee_number'
                )->ignore($managedUser->id),
            ],

            'title' => [
                'nullable',
                'string',
                'max:20',
            ],

            'first_name' => [
                'required',
                'string',
                'max:100',
            ],

            'middle_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'surname' => [
                'required',
                'string',
                'max:100',
            ],

            'username' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique(
                    'users',
                    'username'
                )->ignore($managedUser->id),
            ],

            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique(
                    'users',
                    'email'
                )->ignore($managedUser->id),
            ],

            'work_email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'cell_number' => [
                'nullable',
                'string',
                'max:30',
            ],

            'telephone_number' => [
                'nullable',
                'string',
                'max:30',
            ],

            'phone_extension' => [
                'nullable',
                'string',
                'max:20',
            ],

            'organisation_unit_id' => [
                'required',
                'exists:organisation_units,id',
            ],

            'job_title_id' => [
                'required',
                'exists:job_titles,id',
            ],

            'grade_id' => [
                'nullable',
                'exists:grades,id',
            ],

            'reports_to_user_id' => [
                'nullable',
                'exists:users,id',
                Rule::notIn([
                    $managedUser->id,
                ]),
            ],

            'employment_date' => [
                'nullable',
                'date',
            ],

            'employment_status' => [
                'required',
                Rule::in([
                    'active',
                    'suspended',
                    'terminated',
                    'retired',
                    'seconded',
                    'leave',
                ]),
            ],

            'roles' => [
                'required',
                'array',
                'min:1',
            ],

            'roles.*' => [
                'exists:roles,name',
            ],

            'dashboard_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'dashboard_ids.*' => [
                'exists:dashboards,id',
            ],

            'default_dashboard_id' => [
                'required',
                'exists:dashboards,id',
            ],
        ];
    }
}