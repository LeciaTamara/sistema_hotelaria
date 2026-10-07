<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Room;
use Symfony\Component\HttpFoundation\Response;

class ReservationService
{
    // Verifica se o quarto tem vagas disponíveis para reservas
    public function verificarDisponibilidadeQuarto(int $roomId, string $checkIn, string $checkOut): bool
    {
        // Limite de reservas por quarto
        $limiteDeReservas = 10;

        // Conta quantas o quarto tem no mesmo período
        $periodoReservas = Reservation::where('room_id', $roomId)
            ->where('check_in', '<=', $checkOut)
            ->where('check_out', '>=', $checkIn)
            ->count();

        // Retorna um valor booleano
        return $periodoReservas < $limiteDeReservas;
    }

    // Função de desconto que calcula o desconto do cupom
    public function cacularDesconto(float $valorInicial, ?string $cupom)
    {
        $desconto = 0.0;
        if ($cupom && strtoupper($cupom) === 'FOCO10') {
            $desconto = $valorInicial * 0.1;
            return $desconto;
        }

        // retorna sem o valor de desconto
        return $desconto;
    }

    // Função de acréscimo que calcula a taxa de cada método utilizado para o pagamento
    public function calcularAcrescimo(array $pagamentos)
    {
        $acrescimo = 0.0;

        foreach ($pagamentos as $pagamento) {
            $metodoPagamento = isset($pagamento['method']) ? strtolower(trim($pagamento['method'])) : '';
            $valorDoPagamento = (float) ($pagamento['value'] ?? 0);

            if ($metodoPagamento === 'cartão de crédito' || $metodoPagamento === 'cartao de credito') {
                // 5% de acréscimo no cartão de crédito
                $acrescimo += $valorDoPagamento * 0.05;
            } elseif ($metodoPagamento === 'pix') {
                // 1% de acréscimo no pix
                $acrescimo += $valorDoPagamento * 0.01;
            }
        }

        return $acrescimo;
    }

    // Função para calcular o valor final da reserva incluído de taxa de pagamento e cumpom quando aplicado
    public function calcularValorFinal(float $valorInicial, ?string $cupom, array $pagamentos)
    {
        // Passa a lista de pagamentos para a função acréscimo
        $valorAdicional = $this->calcularAcrescimo($pagamentos);

        $desconto = $this->cacularDesconto($valorInicial, $cupom);

        $valorFinal = $valorInicial + $valorAdicional - $desconto;

        return [
            'valor_inicial_reserva' => $valorInicial,
            'taxa_pagamentos' => $valorAdicional,
            'cupom_desconto' => $desconto,
            'total' => $valorFinal
        ];
    }
}
