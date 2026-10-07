<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class RoomController extends Controller
{
    // Rota que faz o mapeamento do Swagger para o get de quartos

    #[OA\Get(
        path: '/rooms',
        summary: 'Listar todos os quartos',
        description: 'Retorna uma lista completa de todos os quartos cadastrados no hotel.',
        tags: ['Quartos'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de quartos retornada com sucesso.'
            ),
            new OA\Response(
                response: 500,
                description: 'Falha interna no servidor.'
            )
        ]
    )]
    // Mostra todos os dados do hotel
    public function index()
    {
        return response()->json(Room::with('hotel')->get(), Response::HTTP_OK);
    }

    // Rota que faz o mapeamento do Swagger para o Post de quartos

    #[OA\Post(
        path: '/rooms',
        summary: 'Cadastrar um novo quarto',
        description: 'Cria uma nova acomodacao associada a um hotel existente.',
        tags: ['Quartos'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['hotel_id', 'name'],
                properties: [
                    new OA\Property(property: 'hotel_id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'Suite Luxo Double')
                ]
            )
        )
    )]
    #[OA\Response(response: 201, description: 'Quarto criado com sucesso.')]
    #[OA\Response(response: 422, description: 'Erro de validacao nos campos enviados.')]
    #[OA\Response(response: 500, description: 'Erro interno no servidor.')]
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

    // Rota que faz o mapeamento do Swagger para o get de quartos de acordo com o Id

    #[OA\Get(
        path: '/rooms/{id}',
        summary: 'Buscar quarto por ID',
        description: 'Retorna as informacoes detalhadas de um quarto especifico informando seu ID.',
        tags: ['Quartos'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID unico do quarto', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Quarto localizado com sucesso.'),
            new OA\Response(response: 404, description: 'Quarto nao encontrado no sistema.'),
            new OA\Response(response: 500, description: 'Erro interno no servidor.')
        ]
    )]
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

    // Rota que faz o mapeamento do Swagger para o put de quartos

    #[OA\Put(
        path: '/rooms/{id}',
        summary: 'Atualizar dados do quarto',
        description: 'Atualiza o nome ou vinculo de hotel de uma acomodacao existente.',
        tags: ['Quartos'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID unico do quarto', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Suite Executiva Master')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Quarto atualizado com sucesso.'),
            new OA\Response(response: 404, description: 'Quarto nao encontrado.'),
            new OA\Response(response: 500, description: 'Erro interno.')
        ]
    )]
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

    // Rota que faz o mapeamento do Swagger para o delete de quartos

    #[OA\Delete(
        path: '/rooms/{id}',
        summary: 'Excluir um quarto',
        description: 'Remove permanentemente um quarto do estabelecimento a partir de seu ID.',
        tags: ['Quartos'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID unico do quarto', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Quarto excluido com sucesso.'),
            new OA\Response(response: 404, description: 'Quarto nao encontrado.'),
            new OA\Response(response: 500, description: 'Erro interno.')
        ]
    )]
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
