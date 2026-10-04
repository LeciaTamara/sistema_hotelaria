<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\Reservation;
use App\Models\Guest;
use App\Models\Daily;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class ImportXmlData extends Command
{
    protected $signature = 'import:xml';
    protected $description = 'Importa os dados hoteleiros a partir de arquivos XML';

    public function handle()
    {
        $this->info('Iniciando importação dos XMLs...');

        $hotelsPath = storage_path('app/hotels.xml');
        $roomsPath = storage_path('app/rooms.xml');
        $reservesPath = storage_path('app/reserves.xml');

        DB::transaction(function () use ($hotelsPath, $roomsPath, $reservesPath) {
            
            // 1. IMPORTAR HOTÉIS
            if (file_exists($hotelsPath)) {
                $xml = simplexml_load_file($hotelsPath);
                foreach ($xml->Hotel as $hotelData) {
                    Hotel::updateOrCreate(
                        ['id' => (int) $hotelData['id']],
                        ['name' => (string) $hotelData->Name]
                    );
                }
                $this->info('Hotéis importados/atualizados com sucesso!');
            } else {
                $this->error('Arquivo hotels.xml não encontrado em storage/app/');
            }

            // 2. IMPORTAR QUARTOS
            if (file_exists($roomsPath)) {
                $xml = simplexml_load_file($roomsPath);
                foreach ($xml->Room as $roomData) {
                    Room::updateOrCreate(
                        ['id' => (int) $roomData['id']],
                        [
                            'hotel_id' => (int) $roomData['hotelCode'],
                            'name' => (string) $roomData->Name
                        ]
                    );
                }
                $this->info('Quartos importados/atualizados com sucesso!');
            } else {
                $this->error('Arquivo rooms.xml não encontrado em storage/app/');
            }

            // 3. IMPORTAR RESERVAS
            if (file_exists($reservesPath)) {
                $xml = simplexml_load_file($reservesPath);
                foreach ($xml->Reserve as $reserveData) {
                    $reservation = Reservation::updateOrCreate(
                        ['id' => (int) $reserveData['id']],
                        [
                            'hotel_id'  => (int) $reserveData['hotelCode'],
                            'room_id'   => (int) $reserveData['roomCode'],
                            'check_in'  => (string) $reserveData->CheckIn,
                            'check_out' => (string) $reserveData->CheckOut,
                            'total'     => (float) $reserveData->Total,
                        ]
                    );

                    if (isset($reserveData->Guests->Guest)) {
                        $guestData = $reserveData->Guests->Guest;
                        Guest::updateOrCreate(
                            ['reservation_id' => $reservation->id],
                            [
                                'name'      => (string) $guestData->Name,
                                'last_name' => (string) $guestData->LastName,
                                'phone'     => (string) $guestData->Phone,
                            ]
                        );
                    }

                    $reservation->dailies()->delete();
                    if (isset($reserveData->Dailies->Daily)) {
                        foreach ($reserveData->Dailies->Daily as $dailyData) {
                            Daily::create([
                                'reservation_id' => $reservation->id,
                                'date'           => (string) $dailyData->Date,
                                'value'          => (float) $dailyData->Value,
                            ]);
                        }
                    }

                    $reservation->payments()->delete();
                    if (isset($reserveData->Payments->Payment)) {
                        foreach ($reserveData->Payments->Payment as $paymentData) {
                            Payment::create([
                                'reservation_id' => $reservation->id,
                                'method'         => (string) $paymentData->Method,
                                'value'          => (float) $paymentData->Value,
                            ]);
                        }
                    }
                }
                $this->info('Reservas e dependências importadas com sucesso!');
            } else {
                $this->error('Arquivo reserves.xml não encontrado em storage/app/');
            }
        });

        $this->info('Processo de importação finalizado perfeitamente!');
        return Command::SUCCESS;
    }
}
