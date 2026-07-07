<?php

namespace Database\Seeders;

use App\Enums\RoleUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'nama_lengkap' => 'Administrator Dinas Kominfo',
            'nip' => '197001011990031001',
            'nik' => '1403011101700001',
            'email' => 'admin@bengkaliskab.go.id',
            'password' => 'password',
            'no_hp' => '081200000001',
            'pangkat_gol' => 'Pembina / IV-a',
            'jabatan' => 'Administrator Sistem',
            'instansi' => 'Dinas Komunikasi, Informatika dan Statistik',
            'unit_kerja' => 'Bidang Persandian dan Statistik',
            'role' => RoleUser::Admin,
            'email_verified_at' => now(),
        ]);

        $daftarVerifikator = [
            [
                'nama_lengkap' => 'Budi Santoso, S.Kom',
                'nip' => '198505102010011002',
                'nik' => '1403011005850002',
                'email' => '198505102010011002@bengkaliskab.go.id',
                'no_hp' => '081200000002',
                'pangkat_gol' => 'Penata / III-c',
                'jabatan' => 'Pranata Komputer Ahli Muda',
            ],
            [
                'nama_lengkap' => 'Siti Aminah, S.T',
                'nip' => '199002152015032003',
                'nik' => '1403015502900003',
                'email' => '199002152015032003@bengkaliskab.go.id',
                'no_hp' => '081200000003',
                'pangkat_gol' => 'Penata Muda Tk.I / III-b',
                'jabatan' => 'Analis Sistem Informasi',
            ],
        ];

        foreach ($daftarVerifikator as $v) {
            User::create([
                ...$v,
                'password' => 'password',
                'instansi' => 'Dinas Komunikasi, Informatika dan Statistik',
                'unit_kerja' => 'Bidang Persandian dan Statistik',
                'role' => RoleUser::Verifikator,
                'email_verified_at' => now(),
            ]);
        }

        $daftarPemohon = [
            ['nama_lengkap' => 'Andi Wijaya', 'nip' => '198803122012011004', 'nik' => '1403011203880004', 'pangkat_gol' => 'Penata Muda / III-a', 'jabatan' => 'Staf Administrasi', 'instansi' => 'Dinas Pendidikan dan Kebudayaan', 'unit_kerja' => 'Sekretariat'],
            ['nama_lengkap' => 'Dewi Lestari', 'nip' => '199105202018012005', 'nik' => '1403016005910005', 'pangkat_gol' => 'Penata Muda / III-a', 'jabatan' => 'Bendahara Pengeluaran', 'instansi' => 'Dinas Kesehatan', 'unit_kerja' => 'Bidang Keuangan'],
            ['nama_lengkap' => 'Rudi Hartono', 'nip' => '198007152006041006', 'nik' => '1403011507800006', 'pangkat_gol' => 'Penata Tk.I / III-d', 'jabatan' => 'Kepala Seksi', 'instansi' => 'Badan Kepegawaian Daerah', 'unit_kerja' => 'Bidang Mutasi'],
            ['nama_lengkap' => 'Nurul Hidayah', 'nip' => '199306102019032007', 'nik' => '1403012006930007', 'pangkat_gol' => 'Penata Muda / III-a', 'jabatan' => 'Analis Kepegawaian', 'instansi' => 'Sekretariat Daerah', 'unit_kerja' => 'Bagian Organisasi'],
            ['nama_lengkap' => 'Eko Prasetyo', 'nip' => '198909012014031008', 'nik' => '1403010109890008', 'pangkat_gol' => 'Penata Muda Tk.I / III-b', 'jabatan' => 'Pengelola Aset', 'instansi' => 'Badan Pengelola Keuangan dan Aset', 'unit_kerja' => 'Bidang Aset'],
        ];

        foreach ($daftarPemohon as $i => $p) {
            User::create([
                ...$p,
                'email' => $p['nip'] . '@bengkaliskab.go.id',
                'password' => 'password',
                'no_hp' => '0812000001' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'role' => RoleUser::Pemohon,
                'email_verified_at' => now(),
            ]);
        }
    }
}
