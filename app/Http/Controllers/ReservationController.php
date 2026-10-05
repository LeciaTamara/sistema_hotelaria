<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Guest;
use App\Models\Daily;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReservationController extends Controller
{
    //Mostra tosas as reservas
    public function index()
    {

        return response()->json(Reservation::with(['hotel', 'room', 'guest', 'dailies', 'payments'])->get(), Response::HTTP_OK);
    }

    //Cadastra uma nova reserva
    public function store(Request $request)
    {
        $validador = Validator::make($request->all(), [
            'hotel_id' => 'required|exists:hotels,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after_or_equal:check_in',
            'total' => 'required|numeric',
            'guest' => 'required|array',
            'guest.name' => 'required|string|max:100',
            'guest.last_name' => 'required|string|max:100',
            'guest.phone' => 'required|string|max:20',
            'dailies.*.date' => 'required|date',
            'dailies.*.value' => 'required|numeric',
            'payments' => 'sometimes|array',
            'payments.*.method' => 'required|string',
            'payments.*.value' => 'required|numeric',

        ],
        [
            'hotel_id.required' => 'O campo hotel_id é obrigatório.',
            'hotel_id.exists' => 'O hotel informado não existe no sistema, verifique o id informado',
            'room_id_required' => 'O campo room_id é obrigatório.',
            'room_id.exists' => 'O quarto informado não existe no sistema, verifique o id informado',
            'check_in.required' => 'A data de check-in é obrigatória.',
            'check_out.required' => 'A data de check_out é obrigatória',
            'check_out.after_or_equal' => 'A data de check-out deve ser igual ou maior que a data de check-in.',
            'total.required' => 'O valor total da reserva é obrigatório.',
            'guest.required' => 'Os dados do hóspede são obrigatórios.',
            'dailies.required' => 'A listagem de diárias é obrigatória',
        ]);

        if($validador->fails()){
            return response()->json([
                'erros' => $validador->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            //Inicia a transação para salvar a reserva no banco de dados,
            //caso ocorra algum erro em um dos dados informados a tabela não
            //é criada.
            $reserva = DB::transaction(function () use ($request){
                $reserva = Reservation::create($request->only([
                    'hotel_id', 
                    'room_id', 
                    'check_in',
                    'check_out',
                    'total'
                ]));

                //Salva o hospéde que será vinculado a reserva criada
                $dadosHospede = $request->input('guest');
                $dadosHospede['reservation_id'] =  $reserva->id;
                Guest::create($dadosHospede);

                //Salva a lista de Diárias
                foreach($request->input('dailies') as $diaria){
                    $diaria['reservation_id'] = $reserva->id;
                    Daily::create($diaria);
                }

                //Salva a lista de pagamentos
                if ($request->has('payments')){
                    foreach($request->input('payments') as $pagamento){
                        $pagamento['reservation_id'] = $reserva->id;
                        Payment::create($pagamento);
                    }
                }

                return $reserva;

            });

            return response()->json([
                'mensagem' => 'Reserva criada com sucesso!',
                'dados' => Reservation::with(['hotel', 'room', 'guest', 'dailies', 'payments'])->find($reserva->id)
            ], Response::HTTP_CREATED);
        }
        catch(\Exception $erro){
            return response()->json([
                'mensagem' => 'Falha ao cadastrar a reserva',
                'erro' => $erro->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
