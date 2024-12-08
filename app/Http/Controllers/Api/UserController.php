<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
        * criei um resource para filtrar os dados enviado ao frontend
        * corrigi a busca do orderby
        * enviei uma paginacao para melhor visualizacao do usuario
     */
    public function index()
    {
        $users = User::orderBy('updated_at', 'desc')->paginate(10);

        return [
            'data' => UserResource::collection($users),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ]
        ];
    }

    /**
        * arrumei o metodo show para ficar mais clean e de facil manutenção, reaproveitando o UserResource
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return new UserResource($user);
    }

    /**
        * Criei um UserStoreRequest para fazer as validacoes dos dados vindos do frontend
        * Criei uma transaction para aplicações mais robustas, assim deixando o codigo mais seguro
        * Reaproveitei novamente o UserResource para enviar os dados para o frontend
     */
    public function store(UserStoreRequest $request)
    {
        try {
            $user = DB::transaction(function () use ($request) {
                return User::create([
                    'name'     => $request->name,
                    'email'    => $request->email,
                    'password' => Hash::make($request->password),
                    'phone'    => $request->phone,
                ]);
            });

            return response()->json([
                'data'    => new UserResource($user),
                'success' => 'Usuário cadastrado com sucesso!',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao cadastrar o usuário.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
        * Criei um UserUpdateRequest para fazer as validacoes dos dados vindos do frontend
        * A transaction tem o mesmo intuito do store
        * Reaproveitei novamente o UserResource para enviar os dados para o frontend
     */
    public function update(UserUpdateRequest $request, $id)
    {
        $user = User::findOrFail($id);

        try {
            DB::transaction(function () use ($request, $user) {
                $user->update([
                    'name'  => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                ]);
            });

            return response()->json([
                'data'    => new UserResource($user),
                'success' => 'Usuário atualizado com sucesso!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar o usuário.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
