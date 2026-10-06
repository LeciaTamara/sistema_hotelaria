<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ReservationApiTest extends TestCase
{
    // Limpa o banco de dados virtual
    use RefreshDatabase;

    public function testCadastrarReserva()
    {
        $hotel = Hotel::create(['id' => 1, 'name' => 'Hotel Foco Central']);
        $quarto = Room::create(['id' => 1, 'hotel_id' => $hotel->id, 'name' => 'single']);

        // Envia um POST
        $resposta = $this->postJson('/api/reservations', [
            'hotel_id' => $hotel->id,
            'room_id' => $quarto->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'total' => 1000.0,
            'cupom_desconto' => 'FOCO10',  // Aplica um desconto de 10%
            'guest' => [
                'name' => 'Adriana',
                'last_name' => 'Brandão',
                'phone' => '123456789',
            ],
            'dailies' => [
                ['date' => '2026-10-10', 'value' => 200.0],
                ['date' => '2026-10-11', 'value' => 200.0],
                ['date' => '2026-10-12', 'value' => 200.0],
                ['date' => '2026-10-13', 'value' => 200.0],
                ['date' => '2026-10-14', 'value' => 200.0]
            ],
            'payments' => [
                ['method' => 'Cartão de Crédito', 'value' => 400],
                ['method' => 'Dinheiro', 'value' => 600]
            ]
        ]);

        $resposta->assertStatus(Response::HTTP_CREATED);
        $resposta->assertJsonFragment(['total' => '920.00']);

        // verifica se salvou o valor correto
        $this->assertDatabaseHas('reservations', [
            'room_id' => $quarto->id,
            'total' => 920.0
        ]);
    }

    public function testNegarReservaPorErrorDeValidacao()
    {
        $hotel = Hotel::create(['id' => 1, 'name' => 'Hotel de Teste']);
        $quarto = Room::create(['id' => 1, 'hotel_id' => $hotel->id, 'name' => 'Master']);

        // Tenta enviar sem os dados obrigatórios de hóspedes
        $resposta = $this->postJson('/api/reservations', [
            'hotel_id' => $hotel->id,
            'room_id' => $quarto->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'total' => 500.0
        ]);

        $resposta->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $resposta->assertJsonFragment([
            'guest' => ['Os dados do hóspede são obrigatórios.'],
            'dailies' => ['A listagem de diárias é obrigatória']
        ]);
    }

    public function testBloquearReservaQuandoQuartoAtingirLimite()
    {
        $hotel = Hotel::create(['id' => 1, 'name' => 'Hotel de Teste']);
        $quarto = Room::create(['id' => 1, 'hotel_id' => $hotel->id, 'name' => 'Quarto Deluxe']);

        // Força a criação manual de 10 reservas idênticas
        for ($i = 1; $i <= 10; $i++) {
            Reservation::create([
                'hotel_id' => $hotel->id,
                'room_id' => $quarto->id,
                'check_in' => '2026-10-10',
                'check_out' => '2026-10-15',
                'total' => 500.0
            ]);
        }

        // Tenta fazer a reserva 11 do quarto no mesmo período
        $resposta = $this->postJson('/api/reservations', [
            'hotel_id' => $hotel->id,
            'room_id' => $quarto->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'total' => '500.00',
            'guest' => ['name' => 'Clarita', 'last_name' => 'Nunes', 'phone' => '771234567'],
            'dailies' => [
                ['date' => '2026-10-10', 'value' => 200.0],
                ['date' => '2026-10-11', 'value' => 200.0],
                ['date' => '2026-10-12', 'value' => 200.0],
                ['date' => '2026-10-13', 'value' => 200.0],
                ['date' => '2026-10-14', 'value' => 200.0]
            ],
            'payments' => [
                ['method' => 'Cartão de Crédito', 'value' => 400],
                ['method' => 'Dinheiro', 'value' => 600]
            ]
        ]);

        $resposta->assertStatus(Response::HTTP_CONFLICT);
        $resposta->assertJsonFragment([
            'mensagem' => 'Desculpe, este quarto não está mais disponível para reservas neste período, o limite já foi atingido'
        ]);
    }
}
