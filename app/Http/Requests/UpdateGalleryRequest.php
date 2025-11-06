<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGalleryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $gallery = $this->route('gallery');
        return $this->user() && $this->user()->id === $gallery->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'access_type' => ['sometimes', 'required', Rule::in(['public', 'private', 'password_protected'])],
            'password' => ['required_if:access_type,password_protected', 'min:6', 'nullable'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The gallery name is required.',
            'access_type.required' => 'Please select an access type.',
            'access_type.in' => 'Invalid access type selected.',
            'password.required_if' => 'Password is required for password-protected galleries.',
            'password.min' => 'Password must be at least 6 characters.',
            'expires_at.after' => 'Expiration date must be in the future.',
        ];
    }
}
