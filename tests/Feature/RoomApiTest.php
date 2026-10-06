<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RoomApiTest extends TestCase
{
    // Limpa o banco de dados de teste a cada rodada
    use RefreshDatabase;

    public function testCadastrarQuartoComSucesso()
    {
        $hotel = Hotel::create(['id' => 1, 'name' => 'Hotel de Teste Automatizao']);

        // Envia uma requisição POST simulando o envio dos dados
        $resposta = $this->postJson('/api/rooms', [
            'hotel_id' => $hotel->id,
            'name' => 'Quarto simples com duas cama'
        ]);

        $resposta->assertStatus(Response::HTTP_CREATED);
        $resposta->assertJsonFragment(['name' => 'Quarto simples com duas cama']);
    }

    public function testnegarCadastroQuartoSemNome()
    {
        $hotel = Hotel::create(['id' => 1, 'name' => 'Hotel Foco Teste']);

        // Envia uma requisição POST simulando o envio dos dados
        // envia o nome do quarto em branco para força ao erro
        $resposta = $this->postJson('/api/rooms', [
            'hotel_id' => $hotel->id,
            'name' => ''
        ]);

        // Valida se ocorreu o erro de validação
        $resposta->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        // Mostra o erro
        $resposta->assertJsonStructure(['erros' => ['name']]);
    }

    public function testMostrarQuartosComSucesso()
    {
        $hotel = Hotel::create(['id' => 1, 'name' => 'Hotel Foco']);
        Room::create(['hotel_id' => $hotel->id, 'name' => 'Quarto Presidencial']);
        Room::create(['hotel_id' => $hotel->id, 'name' => 'Quarto Deluxe']);

        // Envia uma requisão GET
        $resposta = $this->getJson('/api/rooms');

        $resposta->assertStatus(Response::HTTP_OK);
        $resposta->assertSeeText('Quarto Presidencial');
        $resposta->assertSeeText('Quarto Deluxe');
    }

    public function testMostrarQuartosPorIdComSucesso()
    {
        $hotel = Hotel::create([
            'id' => 1,
            'name' => 'Hotel Central',
        ]);

        $quarto = Room::create(['hotel_id' => $hotel->id, 'name' => 'Quarto Deluxe']);

        $resposta = $this->getJson("/api/rooms/{$quarto->id}");

        $resposta->assertStatus(Response::HTTP_OK);
        $resposta->assertJsonFragment(['name' => 'Quarto Deluxe']);
    }

    public function testAtualizarQuartoComSucesso()
    {
        $hotel = Hotel::create(['id' => 1, 'name' => 'Hotel Central']);
        $quarto = Room::create(['hotel_id' => $hotel->id, 'name' => 'Quarto Presidencial']);

        $resposta = $this->putJson("/api/rooms/{$quarto->id}", [
            'name' => 'Quarto Master'
        ]);

        $resposta->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('rooms', [
            'id' => $quarto->id,
            'name' => 'Quarto Master'
        ]);
    }

    public function testDeletarQuartoComSucesso()
    {
        $hotel = Hotel::create(['id' => 1, 'name' => 'Hotel Central']);
        $quarto = Room::create(['hotel_id' => $hotel->id, 'name' => 'Quarto Double']);

        $resposta = $this->deleteJson("/api/rooms/{$quarto->id}");

        $resposta->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing('rooms', [
            'id' => $quarto->id
        ]);
    }

    public function testBuscarQuartoInexistente()
    {
        // busca um id de quarto que não existe no banco
        $resposta = $this->getJson('/api/rooms/999');

        $resposta->assertStatus(Response::HTTP_NOT_FOUND);
        $resposta->assertJsonFragment(['mensagem' => 'Quarto não encontrado, verifique o id informado.']);
    }

    public function testAtualizarQuartoInexistente()
    {
        // tenta alterar um quarto que não existe
        $resposta = $this->putJson('/api/rooms/888', [
            'name' => 'Suíte'
        ]);

        $resposta->assertStatus(Response::HTTP_NOT_FOUND);
        $resposta->assertJsonFragment(['mensagem' => 'Quarto não encontrado, verifique o id informado.']);
    }

    public function testDeletarQuartoInexistente()
    {
        // tenta apagar um quarto que não existe
        $resposta = $this->deleteJson('/api/rooms/777');

        $resposta->assertStatus(Response::HTTP_NOT_FOUND);
        $resposta->assertJsonFragment(['mensagem' => 'Quarto não encontrado, verifique o id informado.']);
    }
}
