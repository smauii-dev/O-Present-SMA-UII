<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportService
{
    protected const DEFAULT_BORDER = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => '00000000'],
            ],
        ],
    ];

    public function export(array $config): \CodeIgniter\HTTP\ResponseInterface
    {
        $spreadsheet = new Spreadsheet();
        /** @var Worksheet $ws */
        $ws = $spreadsheet->getActiveSheet();

        $this->writeHeader($ws, $config);
        $this->writeFilters($ws, $config);
        $this->writeColumnHeaders($ws, $config);
        $this->writeDataRows($ws, $config);
        $this->applyStyles($ws, $config);

        $tmpFile = WRITEPATH . 'export_' . $config['filename_prefix'] . '_' . time() . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($tmpFile);
        $filename = $config['filename_prefix'] . '_' . date('Y-m-d-His') . '.xlsx';
        $response = response()->download($filename, file_get_contents($tmpFile));
        unlink($tmpFile);

        return $response;
    }

    protected function writeHeader(Worksheet $ws, array $config): void
    {
        $ws->setCellValue('A1', $config['title']);
        $ws->mergeCells('A1:' . $this->colLetter(count($config['columns'])) . '1');
    }

    protected function writeFilters(Worksheet $ws, array $config): void
    {
        if (empty($config['filters'])) {
            return;
        }
        $row = 3;
        foreach ($config['filters'] as $label => $value) {
            $ws->setCellValue('A' . $row, $label);
            $ws->mergeCells('A' . $row . ':B' . $row);
            $ws->setCellValue('C' . $row, $value ?? 'Semua');
            $row++;
        }
        $config['_filter_last_row'] = $row - 1;
    }

    protected function writeColumnHeaders(Worksheet $ws, array $config): void
    {
        $startRow = ($config['_filter_last_row'] ?? 2) + 1;
        $col = 'A';
        foreach ($config['columns'] as $header) {
            $ws->setCellValue($col . $startRow, $header);
            $col++;
        }
        $config['_header_row'] = $startRow;
        $config['_data_start_row'] = $startRow + 1;
    }

    protected function writeDataRows(Worksheet $ws, array $config): void
    {
        // Ensure header row is set
        $headerRow = $config['_header_row'] ?? 3;
        $dataStartRow = $config['_data_start_row'] ?? ($headerRow + 1);
        $row = $dataStartRow;
        $nomor = 1;
        $border = self::DEFAULT_BORDER;

        if (!empty($config['data'])) {
            foreach ($config['data'] as $item) {
                $col = 'A';
                foreach ($config['columns'] as $key => $header) {
                    $mapper = $config['mappers'][$key] ?? $key;
                    $value = is_callable($mapper) ? $mapper($item, $nomor) : $this->resolveValue($item, $mapper);
                    $ws->setCellValue($col . $row, $value);
                    $col++;
                }
                $ws->getStyle('A' . ($row - 1) . ':' . $this->colLetter(count($config['columns'])) . $row)->applyFromArray($border);
                $ws->getStyle('A' . ($row - 1) . ':' . $this->colLetter(count($config['columns'])) . $row)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $row++;
                $nomor++;
            }
        } else {
            $ws->setCellValue('A' . $row, 'Tidak Ada Data');
            $ws->mergeCells('A' . $row . ':' . $this->colLetter(count($config['columns'])) . $row);
            $ws->getStyle('A' . ($row - 1) . ':' . $this->colLetter(count($config['columns'])) . $row)->applyFromArray($border);
        }

        $config['_last_row'] = $row;
    }

    protected function applyStyles(Worksheet $ws, array $config): void
    {
        $lastCol = $this->colLetter(count($config['columns']));
        $headerRow = $config['_header_row'] ?? 3;
        $lastRow = $config['_last_row'] ?? $headerRow;

        // Header row style
        $ws->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->getFont()->setBold(true);
        $ws->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Title style
        $ws->getStyle('A1')->getFont()->setBold(true);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $ws->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF00');

        // Filter labels bold
        if (!empty($config['filters'])) {
            $filterLastRow = $config['_filter_last_row'] ?? 3;
            $ws->getStyle('A3:A' . $filterLastRow)->getFont()->setBold(true);
            $ws->getStyle('A3:C' . $filterLastRow)->applyFromArray(self::DEFAULT_BORDER);
        }

        // Column widths
        foreach ($config['column_widths'] ?? [] as $col => $width) {
            $ws->getColumnDimension($col)->setWidth($width);
        }
        foreach ($config['auto_size_columns'] ?? [] as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }
    }

    protected function resolveValue($item, string|callable $key): mixed
    {
        if (is_callable($key)) {
            return $key($item);
        }
        if (is_object($item)) {
            return $item->$key ?? null;
        }
        if (is_array($item)) {
            return $item[$key] ?? null;
        }
        return null;
    }

    protected function colLetter(int $col): string
    {
        $letter = '';
        while ($col > 0) {
            $col--;
            $letter = chr(65 + ($col % 26)) . $letter;
            $col = (int)($col / 26);
        }
        return $letter;
    }

    // ============ Convenience Methods ============

    public function exportPresensiRekap(array $data, array $profile, string $tanggalAwal, string $tanggalAkhir): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->export([
            'title' => 'Rekap Presensi Pegawai',
            'filename_prefix' => 'O-Present_Rekap_Presensi_' . $profile['nama'],
            'filters' => [
                'Tanggal Awal' => $tanggalAwal,
                'Tanggal Akhir' => $tanggalAkhir,
                'Nama Pegawai' => $profile['nama'],
                'NIP' => $profile['nip'],
            ],
            'columns' => ['#', 'TANGGAL MASUK', 'JAM MASUK', 'JAM PULANG', 'TOTAL JAM KERJA', 'TOTAL JAM KETERLAMBATAN'],
            'mappers' => [
                0 => fn($i) => $i['_nomor'] ?? 1,
                1 => 'tanggal_masuk',
                2 => 'jam_masuk',
                3 => 'jam_keluar',
                4 => 'jam_kerja',
                5 => 'keterlambatan',
            ],
            'data' => $data,
            'auto_size_columns' => ['A','B','C','D','E','F'],
            'column_widths' => [],
        ]);
    }

    public function exportPresensiHarian(array $data, string $tanggalAwal, string $tanggalAkhir): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->export([
            'title' => 'Laporan Presensi Harian',
            'filename_prefix' => 'O-Present_Laporan_Harian_' . $tanggalAwal . '_' . $tanggalAkhir,
            'filters' => [
                'Tanggal Awal' => $tanggalAwal,
                'Tanggal Akhir' => $tanggalAkhir,
            ],
            'columns' => ['#', 'KELAS', 'NAMA PEGAWAI', 'TANGGAL MASUK', 'JAM MASUK', 'JAM PULANG', 'TOTAL JAM KERJA', 'TOTAL JAM KETERLAMBATAN'],
            'mappers' => [
                0 => fn($i, $n) => $n,
                1 => 'alamat',
                2 => 'nama',
                3 => 'tanggal_masuk',
                4 => 'jam_masuk',
                5 => 'jam_keluar',
                6 => 'jam_kerja',
                7 => 'keterlambatan',
            ],
            'data' => $data,
            'auto_size_columns' => ['A','B','C','D','E','F','G','H'],
            'column_widths' => [],
        ]);
    }

    public function exportPresensiBulanan(array $data, string $filterBulan, string $filterTahun): \CodeIgniter\HTTP\ResponseInterface
    {
        $namaBulan = \DateTime::createFromFormat('!n', $filterBulan)->format('F');
        return $this->export([
            'title' => 'Laporan Presensi Bulanan',
            'filename_prefix' => 'O-Present_Laporan_Bulanan_' . $namaBulan . '_' . $filterTahun,
            'filters' => [
                'Bulan' => $namaBulan,
                'Tahun' => $filterTahun,
            ],
            'columns' => ['#', 'KELAS', 'NAMA PEGAWAI', 'TANGGAL MASUK', 'JAM MASUK', 'JAM PULANG', 'TOTAL JAM KERJA', 'TOTAL JAM KETERLAMBATAN'],
            'mappers' => [
                0 => fn($i, $n) => $n,
                1 => 'alamat',
                2 => 'nama',
                3 => 'tanggal_masuk',
                4 => 'jam_masuk',
                5 => 'jam_keluar',
                6 => 'jam_kerja',
                7 => 'keterlambatan',
            ],
            'data' => $data,
            'auto_size_columns' => ['A','B','C','D','E','F','G','H'],
            'column_widths' => [],
        ]);
    }

    public function exportPegawai(array $data, array $filters): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->export([
            'title' => 'Data Pegawai',
            'filename_prefix' => 'O-Present_Data_Pegawai',
            'filters' => array_merge([
                'Filter Jabatan' => '',
                'Filter Role Akun' => '',
                'Filter Status' => '',
                'Filter Jenis Kelamin' => '',
                'Filter Lokasi Presensi' => '',
            ], $filters),
            'columns' => ['#', 'NAMA', 'NIP', 'JABATAN', 'ROLE AKUN', 'USERNAME', 'EMAIL', 'NO. HANDPHONE', 'ALAMAT', 'JENIS KELAMIN', 'LOKASI PRESENSI', 'STATUS'],
            'mappers' => [
                0 => fn($i, $n) => $n,
                1 => 'nama',
                2 => 'nip',
                3 => 'jabatan',
                4 => 'role',
                5 => 'username',
                6 => 'email',
                7 => 'no_handphone',
                8 => 'alamat',
                9 => 'jenis_kelamin',
                10 => 'lokasi_presensi',
                11 => fn($d) => (($d->active ?? $d['active'] ?? 0) == 0) ? 'Belum Aktivasi' : 'Sudah Aktivasi',
            ],
            'data' => $data,
            'auto_size_columns' => ['A','B','C','D','E','F','G','H','J','K','L'],
            'column_widths' => ['I' => 300],
        ]);
    }

    public function exportKetidakhadiran(array $data, array $profile, array $filters): \CodeIgniter\HTTP\ResponseInterface
    {
        $bulan = \DateTime::createFromFormat('Y-m-d', date('Y') . '-' . ($filters['bulan'] ?? date('m')) . '-01');
        $namaBulan = $bulan->format('F');
        $tipeDisplay = $filters['tipe'] ?? 'Semua Tipe';
        $statusDisplay = $filters['status'] ?? 'Semua Status';

        return $this->export([
            'title' => 'Data Ketidakhadiran',
            'filename_prefix' => 'O-Present_Ketidakhadiran_' . $profile['username'] . '_' . $namaBulan . '_' . ($filters['tahun'] ?? date('Y')),
            'filters' => [
                'Nama' => $profile['nama'],
                'NIP' => $profile['nip'],
                'Bulan' => $namaBulan,
                'Tahun' => $filters['tahun'] ?? date('Y'),
                'Filter Tipe' => $tipeDisplay,
                'Filter Status' => $statusDisplay,
            ],
            'columns' => ['#', 'TIPE', 'TANGGAL MULAI', 'TANGGAL BERAKHIR', 'TOTAL DURASI', 'STATUS PENGAJUAN', 'DESKRIPSI'],
            'mappers' => [
                0 => fn($i, $n) => $n,
                1 => 'tipe_ketidakhadiran',
                2 => 'tanggal_mulai',
                3 => 'tanggal_berakhir',
                4 => function($d) {
                    $tMulai = new \DateTime($d->tanggal_mulai ?? $d['tanggal_mulai']);
                    $tAkhir = new \DateTime($d->tanggal_berakhir ?? $d['tanggal_berakhir']);
                    return sprintf('%d Hari', $tMulai->diff($tAkhir)->days + 1);
                },
                5 => 'status_pengajuan',
                6 => 'deskripsi',
            ],
            'data' => $data,
            'auto_size_columns' => ['A','B','C','D','E','F'],
            'column_widths' => ['G' => 250],
        ]);
    }

    public function exportKetidakhadiranAdmin(array $data, array $filters): \CodeIgniter\HTTP\ResponseInterface
    {
        $bulan = \DateTime::createFromFormat('Y-m-d', date('Y') . '-' . ($filters['bulan'] ?? date('m')) . '-01');
        $namaBulan = $bulan->format('F');
        $tipeDisplay = $filters['tipe'] ?? 'Semua Tipe';
        $statusDisplay = $filters['status'] ?? 'Semua Status';

        return $this->export([
            'title' => 'Laporan Ketidakhadiran',
            'filename_prefix' => 'O-Present_Laporan_Ketidakhadiran_' . $namaBulan . '_' . ($filters['tahun'] ?? date('Y')),
            'filters' => [
                'Bulan' => $namaBulan,
                'Tahun' => $filters['tahun'] ?? date('Y'),
                'Tipe' => $tipeDisplay,
                'Status' => $statusDisplay,
            ],
            'columns' => ['#', 'NIP', 'NAMA PEGAWAI', 'TIPE', 'TANGGAL MULAI', 'TANGGAL BERAKHIR', 'TOTAL DURASI', 'STATUS PENGAJUAN', 'DESKRIPSI'],
            'mappers' => [
                0 => fn($i, $n) => $n,
                1 => 'nip',
                2 => 'nama',
                3 => 'tipe_ketidakhadiran',
                4 => 'tanggal_mulai',
                5 => 'tanggal_berakhir',
                6 => function($d) {
                    $tMulai = new \DateTime($d->tanggal_mulai ?? $d['tanggal_mulai']);
                    $tAkhir = new \DateTime($d->tanggal_berakhir ?? $d['tanggal_berakhir']);
                    return sprintf('%d Hari', $tMulai->diff($tAkhir)->days + 1);
                },
                7 => 'status_pengajuan',
                8 => 'deskripsi',
            ],
            'data' => $data,
            'auto_size_columns' => ['A','B','C','D','E','F','G','H'],
            'column_widths' => ['I' => 250],
        ]);
    }

    public function exportLokasiPresensi(array $data, array $filters): \CodeIgniter\HTTP\ResponseInterface
    {
        $tipeDisplay = $filters['tipe'] ?? 'Semua Tipe';
        $waktuDisplay = $filters['waktu'] ?? 'Semua Zona Waktu';

        return $this->export([
            'title' => 'Data Lokasi Presensi',
            'filename_prefix' => 'O-Present_Data_Lokasi_Presensi',
            'filters' => [
                'Filter Tipe' => $tipeDisplay,
                'Filter Zona Waktu' => $waktuDisplay,
            ],
            'columns' => ['#', 'NAMA LOKASI', 'ALAMAT', 'TIPE', 'LATITUDE', 'LONGITUDE', 'RADIUS (m)', 'ZONA WAKTU', 'JAM MASUK', 'JAM PULANG'],
            'mappers' => [
                0 => fn($i, $n) => $n,
                1 => 'nama_lokasi',
                2 => 'alamat_lokasi',
                3 => 'tipe_lokasi',
                4 => 'latitude',
                5 => 'longitude',
                6 => 'radius',
                7 => 'zona_waktu',
                8 => 'jam_masuk',
                9 => 'jam_pulang',
            ],
            'data' => $data,
            'auto_size_columns' => ['A','B','D','E','F','G','H','I','J'],
            'column_widths' => ['C' => 150],
        ]);
    }

    public function exportPresensiRekapPegawai(array $data, string $tanggalAwal, string $tanggalAkhir): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->export([
            'title' => 'Rekap Presensi Pegawai',
            'filename_prefix' => 'O-Present_Rekap_Presensi_Pegawai',
            'filters' => [
                'Tanggal Awal' => $tanggalAwal,
                'Tanggal Akhir' => $tanggalAkhir,
            ],
            'columns' => ['#', 'NAMA', 'NIP', 'JABATAN', 'ROLE AKUN', 'USERNAME', 'EMAIL', 'NO. HANDPHONE', 'ALAMAT', 'JENIS KELAMIN', 'LOKASI PRESENSI', 'STATUS'],
            'mappers' => [
                0 => fn($i, $n) => $n,
                1 => 'nama',
                2 => 'nip',
                3 => 'jabatan',
                4 => 'role',
                5 => 'username',
                6 => 'email',
                7 => 'no_handphone',
                8 => 'alamat',
                9 => 'jenis_kelamin',
                10 => 'lokasi_presensi',
                11 => fn($d) => ($d['active'] ?? $d->active ?? 0) == 0 ? 'Belum Aktivasi' : 'Sudah Aktivasi',
            ],
            'data' => $data,
            'auto_size_columns' => ['A','B','C','D','E','F','G','H','J','K','L'],
            'column_widths' => ['I' => 300],
        ]);
    }

    public function downloadPegawaiTemplate(): \CodeIgniter\HTTP\ResponseInterface
    {
        $spreadsheet = new Spreadsheet();
        $ws = $spreadsheet->getActiveSheet();

        $ws->setCellValue('A1', 'Nama Siswa');
        $ws->setCellValue('B1', 'Jenis Kelamin (Perempuan/Laki-laki)');
        $ws->setCellValue('C1', 'Alamat');
        $ws->setCellValue('D1', 'No Handphone');
        $ws->setCellValue('E1', 'ID Jabatan (3 untuk siswa)');
        $ws->setCellValue('F1', 'ID Lokasi Presensi');
        $ws->setCellValue('G1', 'Email');
        $ws->setCellValue('H1', 'Username');
        $ws->setCellValue('I1', 'ID Role (3 untuk siswa)');
        $ws->setCellValue('J1', 'Password');

        $ws->getStyle('A1:J1')->getFont()->setBold(true);
        $ws->getStyle('A1:J1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('ffff00');

        foreach (['A','B','C','D','E','F','G','H','I','J'] as $c) {
            $ws->getColumnDimension($c)->setAutoSize(true);
        }

        $tmpFile = WRITEPATH . 'template_pegawai_' . time() . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($tmpFile);
        $filename = 'Template_Bulk_Insert_Pegawai.xlsx';
        $response = response()->download($filename, file_get_contents($tmpFile));
        unlink($tmpFile);
        return $response;
    }
}