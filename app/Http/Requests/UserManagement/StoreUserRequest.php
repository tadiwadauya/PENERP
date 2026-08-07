<?php

namespace App\Http\Requests\UserManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'user-management.users.create'
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_number' => [
                'required',
                'string',
                'max:50',
                'unique:users,employee_number',
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
                'unique:users,username',
            ],

            'email' => [
                'required',
                'email',
                'max:150',
                'unique:users,email',
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
                Rule::exists(
                    'organisation_units',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->whereNull('deleted_at')
                ),
            ],

            'job_title_id' => [
                'required',
                Rule::exists(
                    'job_titles',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->whereNull('deleted_at')
                ),
            ],

            'grade_id' => [
                'nullable',
                Rule::exists(
                    'grades',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->whereNull('deleted_at')
                ),
            ],

            'reports_to_user_id' => [
                'nullable',
                'exists:users,id',
            ],

            'employment_date' => [
                'nullable',
                'date',
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

            'password_option' => [
                'required',
                Rule::in([
                    'generate',
                    'manual',
                ]),
            ],

            'temporary_password' => [
                'nullable',
                'required_if:password_option,manual',
                'string',
                'max:128',
            ],
        ];
    }
}