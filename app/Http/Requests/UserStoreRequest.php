<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class UserStoreRequest extends FormRequest
{
    public function failedValidation(Validator $validator)
    {
        // Obter a primeira mensagem de erro
        $firstError = $validator->errors()->first();

        // Lançar a exceção de validação com a primeira mensagem de erro
        throw new ValidationException($validator, response()->json([
            'error' => $firstError,  // Retorna apenas a primeira mensagem de erro
        ], 422));
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $userId = $this->route('user');

        return [
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|max:255|unique:users,email',
            'password'  => 'nullable|string|min:6',
            'phone'     => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'O nome do usuário é obrigatório',
            'email.required'    => 'O email do usuário é obrigatório',
            'email.unique'      => 'Este email já está em uso',
            'password.required' => 'A senha é obrigatória',
            'password.min'      => 'A senha deve ter no mínimo 6 caracteres.',
        ];
    }
}
