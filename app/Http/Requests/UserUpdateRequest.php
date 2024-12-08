<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class UserUpdateRequest extends FormRequest
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
        $userId = $this->route('id');

        return [
            'name'      => 'required|string|max:255',
            'email'     => "required|string|max:255|unique:users,email,{$userId}",
            'phone'     => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'O nome do usuário é obrigatório',
            'email.required'    => 'O email do usuário é obrigatório',
            'email.unique'      => 'Este email já está em uso por outro usuário',
        ];
    }
}
