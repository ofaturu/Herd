<?php

namespace Database\Seeders;

use App\Models\Layanan;
use App\Models\Pengajuan;
use App\Models\RiwayatStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PortalLayananMahasiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Akun Admin / Petugas Layanan (password di-hash aman)
        $admin = User::firstOrCreate(
            ['email' => 'admin@portal.test'],
            [
                'name' => 'Administrator Layanan',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'nim' => null,
                'prodi' => 'Bagian Akademik & Kemahasiswaan',
                'no_hp' => '081234567890',
                'email_verified_at' => now(),
            ]
        );

        // 2. Akun Mahasiswa Simulasi
        $mhs1 = User::firstOrCreate(
            ['email' => 'budi.santoso@mahasiswa.test'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password123'),
                'role' => 'mahasiswa',
                'nim' => '2023001001',
                'prodi' => 'Teknik Informatika',
                'no_hp' => '082198765432',
                'email_verified_at' => now(),
            ]
        );

        $mhs2 = User::firstOrCreate(
            ['email' => 'siti.aminah@mahasiswa.test'],
            [
                'name' => 'Siti Aminah',
                'password' => Hash::make('password123'),
                'role' => 'mahasiswa',
                'nim' => '2023001002',
                'prodi' => 'Sistem Informasi',
                'no_hp' => '085712345678',
                'email_verified_at' => now(),
            ]
        );

        // 3. Master Data Layanan
        $layananList = [
            [
                'kode' => 'SRT-AKTIF',
                'nama' => 'Surat Keterangan Mahasiswa Aktif',
                'deskripsi' => 'Surat resmi yang menerangkan status mahasiswa aktif untuk keperluan beasiswa, BPJS, atau instansi.',
                'persyaratan' => [
                    'KTM (Kartu Tanda Mahasiswa)',
                    'Bukti Pembayaran UKT / KRS Semester Berjalan',
                ],
                'estimasi_hari' => 2,
                'is_aktif' => true,
            ],
            [
                'kode' => 'CUTI-AKD',
                'nama' => 'Pengajuan Cuti Akademik',
                'deskripsi' => 'Permohonan istirahat studi sementara selama satu atau dua semester.',
                'persyaratan' => [
                    'Surat Permohonan Orang Tua / Wali',
                    'Transkrip Nilai Terakhir',
                    'Bebas Tanggungan Perpustakaan & Laboratorium',
                ],
                'estimasi_hari' => 5,
                'is_aktif' => true,
            ],
            [
                'kode' => 'KLASTER-LULUS',
                'nama' => 'Surat Keterangan Lulus (SKL)',
                'deskripsi' => 'Surat keterangan kelulusan sementara sebelum ijazah dan transkrip resmi diterbitkan.',
                'persyaratan' => [
                    'Berita Acara Yudisium',
                    'Bukti Bebas Pustaka & SPP',
                ],
                'estimasi_hari' => 3,
                'is_aktif' => true,
            ],
            [
                'kode' => 'DISPEN-UKT',
                'nama' => 'Dispensasi Pembayaran UKT',
                'deskripsi' => 'Permohonan penundaan atau cicilan pembayaran biaya kuliah semester baru.',
                'persyaratan' => [
                    'Surat Keterangan Penghasilan / Tidak Mampu',
                    'Surat Pernyataan Kesanggupan Membayar',
                ],
                'estimasi_hari' => 4,
                'is_aktif' => true,
            ],
        ];

        $layananModels = [];
        foreach ($layananList as $item) {
            $layananModels[$item['kode']] = Layanan::firstOrCreate(
                ['kode' => $item['kode']],
                $item
            );
        }

        // 4. Data Simulasi Pengajuan dan Riwayat Status
        // Kasus 1: Pengajuan Surat Aktif (status: disetujui)
        $pengajuan1 = Pengajuan::firstOrCreate(
            ['nomor_pengajuan' => 'REQ-202610-0001'],
            [
                'user_id' => $mhs1->id,
                'layanan_id' => $layananModels['SRT-AKTIF']->id,
                'tanggal_pengajuan' => now()->subDays(2)->toDateString(),
                'status' => Pengajuan::STATUS_DISETUJUI,
                'keterangan' => 'Keperluan pengurusan tunjangan gaji orang tua (PNS).',
                'catatan_admin' => 'Dokumen lengkap, surat telah diterbitkan dan dapat diunduh.',
                'dokumen_pendukung' => [
                    ['nama' => 'ktm_budi.pdf', 'path' => 'dokumen/ktm_budi.pdf'],
                    ['nama' => 'krs_semester_gasal.pdf', 'path' => 'dokumen/krs_semester_gasal.pdf'],
                ],
            ]
        );

        if ($pengajuan1->riwayatStatus()->count() === 0) {
            RiwayatStatus::create([
                'pengajuan_id' => $pengajuan1->id,
                'user_id' => $mhs1->id,
                'status_sebelumnya' => null,
                'status_baru' => Pengajuan::STATUS_DIAJUKAN,
                'catatan' => 'Pengajuan baru disubmit oleh mahasiswa.',
                'waktu' => now()->subDays(2),
            ]);

            RiwayatStatus::create([
                'pengajuan_id' => $pengajuan1->id,
                'user_id' => $admin->id,
                'status_sebelumnya' => Pengajuan::STATUS_DIAJUKAN,
                'status_baru' => Pengajuan::STATUS_DIPROSES,
                'catatan' => 'Verifikasi berkas persyaratan oleh admin.',
                'waktu' => now()->subDays(1),
            ]);

            RiwayatStatus::create([
                'pengajuan_id' => $pengajuan1->id,
                'user_id' => $admin->id,
                'status_sebelumnya' => Pengajuan::STATUS_DIPROSES,
                'status_baru' => Pengajuan::STATUS_DISETUJUI,
                'catatan' => 'Dokumen lengkap, surat telah diterbitkan dan dapat diunduh.',
                'waktu' => now()->subHours(5),
            ]);
        }

        // Kasus 2: Pengajuan Cuti (status: diajukan / masih baru)
        $pengajuan2 = Pengajuan::firstOrCreate(
            ['nomor_pengajuan' => 'REQ-202610-0002'],
            [
                'user_id' => $mhs2->id,
                'layanan_id' => $layananModels['CUTI-AKD']->id,
                'tanggal_pengajuan' => now()->toDateString(),
                'status' => Pengajuan::STATUS_DIAJUKAN,
                'keterangan' => 'Rencana magang industri di luar negeri selama 1 semester.',
                'catatan_admin' => null,
                'dokumen_pendukung' => [
                    ['nama' => 'surat_izin_wali.pdf', 'path' => 'dokumen/surat_izin_wali.pdf'],
                ],
            ]
        );

        if ($pengajuan2->riwayatStatus()->count() === 0) {
            RiwayatStatus::create([
                'pengajuan_id' => $pengajuan2->id,
                'user_id' => $mhs2->id,
                'status_sebelumnya' => null,
                'status_baru' => Pengajuan::STATUS_DIAJUKAN,
                'catatan' => 'Pengajuan diajukan oleh mahasiswa, menunggu antrean verifikasi.',
                'waktu' => now(),
            ]);
        }
    }
}
