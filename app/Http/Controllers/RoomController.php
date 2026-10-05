<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    public function index()
    {
        return response()->json(Room::with('hotel')->get(), 200);
    }

    public function store(Request $request)
    {
        $validador = Validator::make($request->all(), [
            'hotel_id' => 'required|exists:hotels,id',
            'name' => 'required|string|max:150',
        ],
            [
                'hotel_id.required' => 'O campo hotel_id é o obrigatório.',
                'hotel_id.exists' => 'O hotrl informado não existe no sistema.',
                'name.require' => 'O nome do quarto é obrigatório.',
            ]);

        if ($validador->fails()) {
            return response()->json(['erros' => $validador->erros()], 422);
        }

        $quarto = Room::create($request->all());

        return response()->json([
            'mensagem' => 'Cadastro do quarto realizado com sucesso!',
            'dados' => $quarto
        ], 201);
    }
}
?>
