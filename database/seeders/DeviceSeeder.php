<?php

namespace Database\Seeders;

use App\Enums\DeviceStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Device;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $hospital = Company::where('email', 'admin@hospitalcentral.com')->first();

        if (!$hospital) {
            $this->command->error('❌ Hospital Central no encontrado. Ejecuta CompanySeeder primero.');
            return;
        }

        // Obtener sucursales
        $branches = Branch::where('company_id', $hospital->id)->get();

        if ($branches->isEmpty()) {
            $this->command->error('❌ No hay sucursales. Ejecuta BranchAndDepartmentSeeder primero.');
            return;
        }

        $edificioPrincipal = $branches->firstWhere('code', 'EP');
        $laboratorioSur = $branches->firstWhere('code', 'LS');

        // Dispositivos de prueba
        $devices = [
            [
                'device_code' => 'DEV001-NFC',
                'name' => 'Lector Entrada Principal',
                'location' => 'Entrada principal - Planta baja',
                'branch_id' => $edificioPrincipal?->id,
                'ip_address' => '192.168.1.101',
                'mac_address' => '00:1B:44:11:3A:B7',
                'config' => [
                    'read_mode' => 'nfc',
                    'sound_enabled' => true,
                    'led_feedback' => true,
                ],
            ],
            [
                'device_code' => 'DEV002-NFC',
                'name' => 'Lector Entrada Urgencias',
                'location' => 'Área de urgencias - Acceso personal',
                'branch_id' => $edificioPrincipal?->id,
                'ip_address' => '192.168.1.102',
                'mac_address' => '00:1B:44:11:3A:B8',
                'config' => [
                    'read_mode' => 'nfc',
                    'sound_enabled' => true,
                    'led_feedback' => true,
                ],
            ],
            [
                'device_code' => 'DEV003-NFC',
                'name' => 'Lector Acceso Laboratorio',
                'location' => 'Laboratorio Sur - Entrada única',
                'branch_id' => $laboratorioSur?->id,
                'ip_address' => '192.168.2.101',
                'mac_address' => '00:1B:44:11:3A:B9',
                'config' => [
                    'read_mode' => 'nfc',
                    'sound_enabled' => true,
                    'led_feedback' => true,
                    'restricted_access' => true,
                ],
            ],
        ];

        $createdDevices = [];
        foreach ($devices as $deviceData) {
            $device = Device::firstOrCreate(
                [
                    'company_id' => $hospital->id,
                    'device_code' => $deviceData['device_code'],
                ],
                array_merge(['company_id' => $hospital->id], $deviceData, [
                    'status' => DeviceStatus::ACTIVO,
                    'last_ping_at' => now()->subMinutes(rand(1, 5)),
                ])
            );
            $createdDevices[] = $device;
        }

        $this->command->info('✅ Dispositivos NFC creados para Hospital Central');
        $this->command->info('   - ' . count($createdDevices) . ' dispositivos creados y activos');
        foreach ($createdDevices as $device) {
            $status = $device->isOnline() ? '🟢 Online' : '🔴 Offline';
            $this->command->info("   - {$device->name} ({$device->device_code}) {$status}");
        }
    }
}
