<?php

namespace App\Http\Controllers;

use App\Models\Daily;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class ReservationController extends Controller
{
    // Rota que faz o mapeamento do Swagger para o get de reservas
    #[OA\Get(
        path: '/reservations',
        summary: 'Listar todas as reservas',
        description: 'Retorna uma lista completa de todas as reservas cadastradas com hóspedes, diárias e pagamentos.',
        tags: ['Reservas']
    )]
    #[OA\Response(response: 200, description: 'Lista de reservas retornada com sucesso.')]
    #[OA\Response(response: 500, description: 'Erro interno no servidor.')]
    // Mostra tosas as reservas
    public function index()
    {
        return response()->json(Reservation::with(['hotel', 'room', 'guest', 'dailies', 'payments'])->get(), Response::HTTP_OK);
    }

    // Variável utilizada para guardar a classe ReservationService
    protected $gerenciaReservas;

    // O reservationSevice no parâmentro do construtur diz ao laravel para colocar a classe ReservationService dentro do controle
    // e dar a essa classe o nome temporário de reservationservice;
    public function __construct(ReservationService $reservationService)
    {
        // guarda a classe ReservationService injetada pelo o laravél($reservationService), e guarda dentro da variável de $gerenciaReservas
        // a variável $calculoDeServico agora será chamada para invocar todas as funções do arquivo ReservationService
        $this->gerenciaReservas = $reservationService;
    }

    // Rota que faz o mapeamento do Swagger para o Post de reservas

    #[OA\Post(
        path: '/reservations',
        summary: 'Criar uma nova reserva com regras aplicadas',
        description: 'Processa a ocupacao maxima, calcula taxas progressivas por forma de pagamento e valida cupom de desconto em porcentagem.',
        tags: ['Reservas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['hotel_id', 'room_id', 'check_in', 'check_out', 'total', 'guest', 'dailies'],
                properties: [
                    new OA\Property(property: 'hotel_id', type: 'integer', example: 1),
                    new OA\Property(property: 'room_id', type: 'integer', example: 1),
                    new OA\Property(property: 'check_in', type: 'string', format: 'date', example: '2026-10-10'),
                    new OA\Property(property: 'check_out', type: 'string', format: 'date', example: '2026-10-15'),
                    new OA\Property(property: 'total', type: 'number', format: 'float', example: 1000.0),
                    new OA\Property(property: 'cupom_desconto', type: 'string', example: 'FOCO10'),
                    new OA\Property(
                        property: 'guest',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'name', type: 'string', example: 'Adriana'),
                            new OA\Property(property: 'last_name', type: 'string', example: 'Brandão'),
                            new OA\Property(property: 'phone', type: 'string', example: '123456789')
                        ]
                    ),
                    new OA\Property(
                        property: 'dailies',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-10-10'),
                                new OA\Property(property: 'value', type: 'number', format: 'float', example: 200.0)
                            ]
                        )
                    ),
                    new OA\Property(
                        property: 'payments',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'method', type: 'string', example: 'Cartão de Crédito'),
                                new OA\Property(property: 'value', type: 'number', format: 'float', example: 400.0)
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Reserva efetuada com sucesso aplicando as regras financeiras.'),
            new OA\Response(response: 409, description: 'Conflito: Quarto ja atingiu o limite maximo de 10 reservas concorrentes.'),
            new OA\Response(response: 422, description: 'Erros de validacao de campos.'),
            new OA\Response(response: 500, description: 'Erro interno no servidor.')
        ]
    )]
    // Cadastra uma nova reserva
    public function store(Request $request)
    {
        $validador = Validator::make($request->all(), [
            'hotel_id' => 'required|exists:hotels,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after_or_equal:check_in',
            'total' => 'required|numeric',
            'cupom_desconto' => 'sometimes|string',  // Campo reservado para informar o cupom de desconto
            'guest' => 'required|array',
            'guest.name' => 'required|string|max:100',
            'guest.last_name' => 'required|string|max:100',
            'guest.phone' => 'required|string|max:20',
            'dailies' => 'required|array',
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

        if ($validador->fails()) {
            Log::error('Falha ao validar as informações do quarto', ['erros_encontrados' => $validador->errors()]);

            return response()->json([
                'erros' => $validador->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $roomId = $request->input('room_id');
            $checkIn = $request->input('check_in');
            $checkOut = $request->input('check_out');

            $quartoDisponível = $this->gerenciaReservas->verificarDisponibilidadeQuarto($roomId, $checkIn, $checkOut);

            if (!$quartoDisponível) {
                Log::warning("Reserva negada por falta de disponibilidade para o quarto com o id {$roomId}");

                return response()->json([
                    'mensagem' => 'Desculpe, este quarto não está mais disponível para reservas neste período, o limite já foi atingido'
                ], Response::HTTP_CONFLICT);
            }

            // Captura os dados de pagamento e o cumpom de desconto
            $valorInicial = $request->input('total');
            $cupom = $request->input('cupom_desconto');

            // Captura a lista de pagamentos ou cria uma lista vazia caso o usuário não informe o pagamento
            $pagamentosEnviados = $request->input('payments', []);

            // Chamada da função para calcular os acréscimos do valor da reserva e o desconto quando aplicavél
            $valorFinalDaReserva = $this->gerenciaReservas->calcularValorFinal($valorInicial, $cupom, $pagamentosEnviados);

            // Salva a reserva
            $reserva = Reservation::create([
                'hotel_id' => $request->input('hotel_id'),
                'room_id' => $request->input('room_id'),
                'check_in' => $request->input('check_in'),
                'check_out' => $request->input('check_out'),
                'total' => $valorFinalDaReserva['total']
            ]);

            // Salva o hospede vinculado a reserva
            $dadosHospede = $request->input('guest');
            $dadosHospede['reservation_id'] = $reserva->id;
            Guest::create($dadosHospede);

            // Salva a lista de diárias do hospédes
            foreach ($request->input('dailies') as $diaria) {
                $diaria['reservation_id'] = $reserva->id;
                Daily::create($diaria);
            }

            // Salva a lista do método de pagamento utilizado pelo o hóspede
            if ($request->has('payments')) {
                foreach ($request->input('payments') as $pagamento) {
                    $pagamento['reservation_id'] = $reserva->id;
                    Payment::create($pagamento);
                }
            }

            // Busca os dados trazendo todas as informações de reservas
            $reservaCompleta = Reservation::with(['hotel', 'room', 'guest', 'dailies', 'payments'])->find($reserva->id);

            // Salvar log
            Log::info('Reservas cadastradas com sucesso', ['reservas' => $reservaCompleta, 'dadosFinanceiros' => $valorFinalDaReserva]);

            return response()->json([
                'mensagem' => 'Reserva criada com suscesso!',
                'dados' => Reservation::with(['hotel', 'room', 'guest', 'dailies', 'payments'])->find($reserva->id)
            ], Response::HTTP_CREATED);
        } catch (\Exception $erro) {
            // Log de erro
            Log::error('Falha ao cadastrar a reserva', ['erro' => $erro->getMessage()]);

            return response()->json([
                'mensagem' => 'Falha ao cadastrar a reserva',
                'erro' => $erro->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
