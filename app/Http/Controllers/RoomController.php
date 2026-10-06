<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class RoomController extends Controller
{
    // Mostra todos os dados do hotel
    public function index()
    {
        return response()->json(Room::with('hotel')->get(), Response::HTTP_OK);
    }

    // Cadastra um quarto dentro do hotel
    public function store(Request $request)
    {
        // guarda os dados do processo de validação dos dados informados
        $validador = Validator::make($request->all(), [
            'hotel_id' => 'required|exists:hotels,id',
            'name' => 'required|string|max:150',
        ],
            [
                'hotel_id.required' => 'O campo hotel_id é o obrigatório.',
                'hotel_id.exists' => 'O hotel informado não existe no sistema.',
                'name.require' => 'O nome do quarto é obrigatório.',
            ]);

        if ($validador->fails()) {
            // Log de erro
            Log::error('Falha ao validar as informações de quarto', ['erro' => $validador->errors()]);

            return response()->json(['erros' => $validador->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $quarto = Room::create($request->all());

            // Salvar log
            Log::info('Quarto cadastrado com sucesso', ['quarto' => $quarto]);

            return response()->json([
                'mensagem' => 'Cadastro do quarto realizado com sucesso!',
                'dados' => $quarto
            ], Response::HTTP_CREATED);
        } catch (\Exception $erro) {
            // Log de erro
            Log::error('Falha ao cadastrar o quarto', ['erro' => $validador->errors()]);

            return response()->json([
                'mensagem' => 'Falha ao cadastrar o quarto',
                'erro' => $erro->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Mostra os dados do quarto de acordo com o id informado
    public function show($id)
    {
        try {
            $quarto = Room::with('hotel')->find($id);

            if (!$quarto) {
                // Log de aviso
                Log::warning('Falha ao buscar o quarto, verifique o id informado.', ['id_informado' => $id]);

                return response()->json([
                    'mensagem' => 'Quarto não encontrado, verifique o id informado.'
                ], Response::HTTP_NOT_FOUND);
            }

            // Salvar log
            Log::info('Quarto encontrado com sucesso', ['quarto' => $quarto]);

            return response()->json($quarto, Response::HTTP_OK);
        } catch (\Exception $erro) {
            // Log de erro
            Log::error('Falha no sistema ao buscar o quarto', ['erro' => $erro->getMessage()]);

            return response()->json([
                'mensagem' => 'Falha no sistema ao o quarto',
                'erro' => $erro->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Atualiza os dados do quarto de acordo com o id do quarto informado
    public function update(Request $request, $id)
    {
        $quarto = Room::find($id);
        if (!$quarto) {
            // Log de aviso
            Log::warning('Falha ao atualizar o quarto, verifique o id informado.', ['id_informado' => $id]);

            return response()->json([
                'mensagem' => 'Quarto não encontrado, verifique o id informado.'
            ], Response::HTTP_NOT_FOUND);
        }

        $validador = Validator::make($request->all(), [
            'hotel_id' => 'sometimes|required|exists:hotels,id',
            'name' => 'sometimes|required|string|max:150',
        ],
            [
                'hotel_id.exists' => 'O hotel informado não existe no sistema, verifique o id informado.',
            ]);

        try {
            $quarto->update($request->all());

            // Salvar log
            Log::info('Quarto {$id} atualiado com sucesso', ['quarto' => $quarto]);

            return response()->json([
                'mensagem' => 'Quarto atualizado com sucesso!', 'dados' => $quarto
            ], Response::HTTP_OK);
        } catch (\Exception $erro) {
            // Log de erro
            Log::error('Falha no sistema ao tentar atualizar o quarto', ['erro' => $erro->getMessage()]);

            return response()->json([
                'mensagem' => 'Falha no sistema ao atualizar o quarto',
                'erro' => $erro->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Apagar o quarto de acordo com o id informado
    public function destroy($id)
    {
        $quarto = Room::find($id);

        if (!$quarto) {
            // Log de aviso
            Log::warning('Falha ao buscar o quarto, verifique o id informado.', ['id_informado' => $id]);

            return response()->json([
                'mensagem' => 'Quarto não encontrado, verifique o id informado.'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            // Guarda os dados do quarto que será excluído
            $dadosExcluido = $quarto->toArray();

            $quarto->delete();

            Log::info('Quarto apagado com sucesso', ['dadosExcluido' => $dadosExcluido]);

            return response()->json([
                'mensagem' => 'Quarto removido com sucesso!'
            ], Response::HTTP_OK);
        } catch (\Exception $erro) {
            Log::error('Falha no sistema ao tentar apagar o quarto.', ['erro' => $erro->getMessage()]);

            return response()->json([
                'mensagem' => 'Falha no sistema ao tentar apagar o quarto.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
?>
