<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RiddleRequest extends FormRequest
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
            'niveau' => 'required|integer|between:1,3',
            'description' => 'required|string',
            'reponse' => 'required|string|max:255',
            'mcq_options' => 'nullable|array',
            'images' => 'nullable|array|max:3',
            'images.*' => 'nullable|image|max:2048',
            'hints' => 'nullable|array',
            'hints.*.type' => 'required|string|in:text,image,keyword,description',
            'hints.*.content' => 'required|string',
            'hints.*.difficulty_level' => 'nullable|string|in:easy,medium,hard',
        ];
    }

    /**
     * Prépare les données validées en filtrant les options MCQ vides
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Filtrer les options vides pour ne pas stocker de tableau de chaînes vides
        if (isset($validated['mcq_options'])) {
            $validated['mcq_options'] = array_values(array_filter($validated['mcq_options'], function($val) {
                return !is_null($val) && trim($val) !== '';
            }));
            
            if (empty($validated['mcq_options'])) {
                $validated['mcq_options'] = null;
            }
        }

        return $validated;
    }
}
