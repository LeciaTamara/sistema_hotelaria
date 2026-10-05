<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
            return response()->json(['erros' => $validador->erros()], Response:HTTP_UNPROCESSABLE_ENTITY);
        }

        $quarto = Room::create($request->all());

        return response()->json([
            'mensagem' => 'Cadastro do quarto realizado com sucesso!',
            'dados' => $quarto
        ], Response::HTTP_CREATED);
    }

    // Mostra os dados do quarto de acordo com o id informado
    public function show($id)
    {
        $quarto = Room::with('hotel')->find($id);
        if (!$quarto) {
            return response()->json([
                'mensagem' => 'Quarto não encontrado, verifique o id informado.'
            ], Response::HTTP_NOT_FOUND);
        }
        return response()->json($quarto, Response::HTTP_OK);
    }

    // Atualiza os dados do quarto de acordo com o id do quarto informado
    public function update(Request $request, $id)
    {
        $quarto = Room::find($id);
        if (!$quarto) {
            return response()->json([
                'mensagem' => 'Quarto não encontrado, verifique o id informado'
            ], Response::HTTP_NOT_FOUND);
        }

        $validador = Validator::make($request->all(), [
            'hotel_id' => 'sometimes|required|exists:hotels,id',
            'name' => 'sometimes|required|string|max:150',
        ],
            [
                'hotel_id.exists' => 'O hotel informado não existe no sistema, verifique o id informado.',
            ]);

        if ($validador->fails()) {
            return response()->json([
                'erros' => $validador->erros()
            ], Response:HTTP_UNPROCESSABLE_ENTITY);
        }

        $quarto->update($request->all());
        return response()->json([
            'mensagem' => 'Quarto atualizado com sucesso!', 'dados' => $quarto
        ], Response::HTTP_OK);
    }

    // Apagar o quarto de acordo com o id informado
    public function destroy($id)
    {
        $quarto = Room::find($id);

        if (!$quarto) {
            return response()->json([
                'mensagem' => 'Quarto não encontrado, verifique o id informado.'
            ], Response::HTTP_NOT_FOUND);
        }

        $quarto->delete();
        return response()->json([
            'mensagem' => 'Quarto removido com sucesso!'
        ], Response::HTTP_OK);
    }
}
?>
