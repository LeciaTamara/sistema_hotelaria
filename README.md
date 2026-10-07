# API REST - Sistema de Gestão Hoteleira

**Desenvolvido por:** Lécia Tamara 
**GitHub:** [://github.com](https://github.com/LeciaTamara)

Este projeto consiste em uma API REST de alta performance desenvolvida em **Laravel 13** e **PHP 8.5** para a gestão unificada de hotéis, quartos e reservas. O sistema conta logs, testes automatizados utilizando PHPUnit e documentação Swagger.

---

## Engenharia e Modelagem do Banco de Dados

A arquitetura do banco de dados relacional foi inteiramente projetada e normalizada utilizando a ferramenta **MySQL Workbench**, tomando como base analítica as tags e relacionamentos presentes nos arquivos XML fornecidos pela empresa.

O arquivo físico com o diagrama ERR encontra-se no caminho:
**`database/design/modelagem.mwb`**

## Estrutura das Tabelas Criadas via Migrations:
* **`hotels`**: Armazena as propriedades principais dos hotéis (ID, Nome).
* **`rooms`**: Define quartos, com (ID, NOME, HOTEL_ID)
* **`reservations`**: Cria as resservas com os dados de (CHECK_IN, CHECK_OUT, QUARTO_ID, HOTEL_ID, GUEST, DAILIES E PAYMENTS).
* **`guests`**: Armazena os dados cadastrais e de contato do hóspede titular da reserva.
* **`dailies`**: Linhas de registro que quebram o valor individual de cada dia do calendário da reserva.
* **`payments`**: Armazena as formas de pagamento utilizadas (Cartão, Pix, Dinheiro) e os respectivos valores parcelados.

---

## Configuração e Inicialização do Ambiente (Docker Sail)

A aplicação utiliza o **Laravel Sail** para orquestrar contêineres Docker isolados contendo o servidor web **Nginx**, o processador **PHP-FPM** e o servidor de banco de dados **MySQL**, garantindo que o sistema rode de forma idêntica em qualquer máquina.

### Passo a Passo para Subir o Sistema:

## Comandos para ligar o Docker
sudo systemctl start docker

## Comando parar o docker e o socket:
sudo systemctl stop docker docker.socket

1. **Instalar dependências isoladamente (Composer):**
   ```bash
   docker run --rm \
       -v "$(pwd):/var/www/html" \
       -w /var/www/html \
       laravelsail/php83-composer:latest \
       composer install --ignore-platform-reqs
   ```
2. **Configurar as variáveis de ambiente:**
   ```bash
   cp .env.example .env
   ```
3. **Inicializar os contêineres em segundo plano:**
   ```bash
   ./vendor/bin/sail up -d
   ```
4. **Gerar chave de criptografia e rodar as Migrations com dados iniciais (Seeders):**
   ```bash
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate --seed
   ```

---


## Script de Importação Automática XML e Configuração do CRON

Como exigido pelo desafio, foi desenvolvido um comando customizado do Artisan em PHP chamado **`import:xml`**. Este script realiza a leitura de arquivos XML contidos no projeto, faz a recuperação assíncrona dos dados e faz a persistência correta no banco de dados respeitando a integridade das chaves estrangeiras.

### ⚙️ Como Executar o CRON no Linux:

O comando foi acoplado ao agendador de tarefas nativo do Laravel (`app/Console/Kernel.php` ou `routes/console.php`) para ser executado de forma automática em segundo plano pelo sistema operacional.

Para ativar a automação no seu Ubuntu local:
1. Abra o editor do agendador do Linux:
   ```bash
   crontab -e
   ```
2. Insira a diretiva de execução no final do arquivo (substituindo pelo caminho real da pasta do seu projeto):
   ```text
   * * * * * cd /caminho/absoluto/do/seu/projeto && ./vendor/bin/sail artisan schedule:run >> /dev/null 2>&1
   ```
3. Salve o arquivo. O Linux passará a invocar o agendador do Laravel a cada minuto, disparando o script de importação XML automaticamente.

---

## Guia de Uso da API (Processos de Execução)

Todas as requisições trafegam sob o protocolo HTTP utilizando dados formatados em JSON.

## Como Cadastrar um Quarto
* **Rota:** `POST /api/rooms`
* **Payload JSON:**
```json
{
  "hotel_id": 1,
  "name": "Quarto Luxo Superior com Duas Camas"
}
```
* **Respostas Esperadas:** `201 Created` (Sucesso) ou `422 Unprocessable Entity` (Se o nome for omitido ou hotel não existir).

## Como Cadastrar uma Reserva (Com Regras de Negócio Avançadas)
Esta rota envia o dado complexo contendo o hóspede, a quebra de diárias e os pagamentos. O sistema valida automaticamente se o quarto já atingiu o limite de 10 reservas simultâneas no período, aplica 10% de desconto para o cupom `FOCO10`, adiciona 5% de taxa para Cartão de Crédito e 1% para Pix.
* **Rota:** `POST /api/reservations`
* **Payload JSON:**
```json
{
  "hotel_id": 1,
  "room_id": 1,
  "check_in": "2026-10-10",
  "check_out": "2026-10-15",
  "total": 1000.00,
  "cupom_desconto": "FOCO10",
  "guest": {
    "name": "Adriana",
    "last_name": "Brandão",
    "phone": "123456789"
  },
  "dailies": [
    { "date": "2026-10-10", "value": 200.00 },
    { "date": "2026-10-11", "value": 200.00 },
    { "date": "2026-10-12", "value": 200.00 },
    { "date": "2026-10-13", "value": 200.00 },
    { "date": "2026-10-14", "value": 200.00 }
  ],
  "payments": [
    { "method": "Cartão de Crédito", "value": 400.00 },
    { "method": "Dinheiro", "value": 600.00 }
  ]
}
```
* **Respostas Esperadas:** 
  * `201 Created`: Reserva criada com sucesso exibindo o cálculo do faturamento líquido.
  * `409 Conflict`: Barreira de calendário acionada (Quarto atingiu o limite de 10 vagas no período).
  * `422 Unprocessable Entity`: Falha de validação de campos obrigatórios.

## Como Listar Quartos e Reservas
* **Listar Quartos:** `GET /api/rooms` (Retorna todos os quartos e os hotéis vinculados).
* **Listar Reservas:** `GET /api/reservations` (Retorna o histórico completo com hóspedes e dados do valor da reserva).

---

## Qualidade de Código e Testes Automatizados (PHPUnit)

Para garantir a estabilidade dos dados durante a execução das rotas sem afetar o banco MySQL de desenvolvimento, os testes utilizam um banco virtual SQLite em memória RAM.

Para rodar os testes via contêiner:
```bash
# Executa os 9 testes lógicos do CRUD de Quartos
./vendor/bin/sail test tests/Feature/RoomApiTest.php

# Executa os testes de validação, cupom e limite de vagas de Reservas
./vendor/bin/sail test tests/Feature/ReservationApiTest.php
```

---

## Documentação OpenAPI Interativa (Swagger)

A API possui uma interface web do Swagger para testar os endpoints clicando em botões diretamente no navegador.

Para compilar e visualizar:
1. Gere os arquivos de documentação no terminal:
   ```bash
   ./vendor/bin/sail artisan l5-swagger:generate
   ```
2. Acesse no seu navegador:
   **`http://localhost/api/documentation`**

