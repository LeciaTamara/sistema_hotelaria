<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;  // Importação do Swagger

// Variáveis globais do Swagger
#[OA\Info(
    title: 'API de Gestão Hoteleira',
    version: '1.0.0',
    description: 'Documentação interativa das APIs de Hotéis, Quartos e Reservas do desafio técnico.'
)]
#[OA\Server(
    url: 'http://localhost/api',
    description: 'Servidor Local de Desenvolvimento'
)]
abstract class Controller
{
    //
}
