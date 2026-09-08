<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadProductImageRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Configure the validator instance with custom cross-field requirements.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->hasFile('image') && !$this->hasFile('images')) {
                $validator->errors()->add('image', 'Please provide an image or an array of images to upload.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.image' => 'The uploaded file must be a valid image.',
            'image.mimes' => 'Images must be in jpeg, png, jpg, or webp format.',
            'image.max' => 'The image size must not exceed 4MB.',
            'images.*.image' => 'Each uploaded file must be a valid image.',
            'images.*.mimes' => 'Images must be in jpeg, png, jpg, or webp format.',
            'images.*.max' => 'Each image size must not exceed 4MB.',
        ];
    }
}
