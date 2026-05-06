<?php

namespace App\Exports;

use App\Models\Cpcl;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CpclSheetExport implements
    FromQuery,
    WithTitle,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles
{
    protected string  $bidang;
    protected ?string $page;
    protected array   $filters;

    public function __construct(string $bidang, ?string $page = null, array $filters = [])
    {
        $this->bidang  = $bidang;
        $this->page    = $page;
        $this->filters = $filters;
    }

    // ── Sheet title ───────────────────────────────────────────────────────────

    public function title(): string
    {
        $pageLabel = match ($this->page) {
            'terverifikasi' => 'Terverifikasi',
            'belum'         => 'Belum Verifikasi',
            'perbaikan'     => 'Perlu Perbaikan',
            'ditolak'       => 'Ditolak',
            default         => 'Semua Data',
        };

        return "{$this->bidang} - {$pageLabel}";
    }

    // ── Query (otomatis filter bidang + status sesuai page) ───────────────────

    public function query()
    {
        $query = Cpcl::query()
            ->with('alamat')
            ->where('bidang', $this->bidang);

        // Filter status sesuai page yang di-export
        match ($this->page) {
            'terverifikasi' => $query->where('status', 'terverifikasi'),
            'belum'         => $query->whereNotIn('status', ['terverifikasi', 'ditolak', 'perlu_perbaikan']),
            'perbaikan'     => $query->where('status', 'perlu_perbaikan'),
            'ditolak'       => $query->where('status', 'ditolak'),
            default         => null, // 'index' / null → semua status
        };

        // Terapkan filter opsional (kecamatan, rencana_usaha, search)
        if (!empty($this->filters['kecamatan'])) {
            $query->whereHas('alamat', fn($q) =>
                $q->where('kecamatan', 'LIKE', '%' . $this->filters['kecamatan'] . '%')
            );
        }

        if (!empty($this->filters['rencana_usaha'])) {
            $query->where('rencana_usaha', 'LIKE', '%' . $this->filters['rencana_usaha'] . '%');
        }

        if (!empty($this->filters['search'])) {
            $s = $this->filters['search'];
            $query->where(fn($q) => $q
                ->where('nama_kelompok', 'LIKE', "%$s%")
                ->orWhere('nama_ketua',  'LIKE', "%$s%")
                ->orWhere('nik_ketua',   'LIKE', "%$s%")
            );
        }

        return $query->latest();
    }

    // ── Headings ──────────────────────────────────────────────────────────────

    public function headings(): array
    {
        return [
            'No',
            'Nama Kelompok',
            'Nama Ketua',
            'NIK Ketua',
            'Bidang',
            'Rencana Usaha',
            'Kabupaten',
            'Kecamatan',
            'Desa',
            'Lokasi',
            'Luas Lahan (Ha)',
            'Status Lahan',
            'Lama Berdiri (Thn)',
            'Hasil Panen (Ton)',
            'Status',
            'Catatan Verifikator',
            'Tanggal Daftar',
        ];
    }

    // ── Row mapping ───────────────────────────────────────────────────────────

    private int $rowNumber = 0;

    public function map($cpcl): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $cpcl->nama_kelompok,
            $cpcl->nama_ketua,
            $cpcl->nik_ketua,
            $cpcl->bidang,
            $cpcl->rencana_usaha,
            $cpcl->alamat?->kabupaten   ?? '-',
            $cpcl->alamat?->kecamatan   ?? '-',
            $cpcl->alamat?->desa        ?? '-',
            $cpcl->lokasi,
            $cpcl->luas_lahan,
            ucfirst(str_replace('_', ' ', $cpcl->status_lahan)),
            $cpcl->lama_berdiri,
            $cpcl->hasil_panen,
            $cpcl->status_label,          // pakai accessor yang sudah ada di model
            $cpcl->catatan_verifikator ?? '-',
            $cpcl->created_at?->format('d/m/Y') ?? '-',
        ];
    }

    // ── Header styling ────────────────────────────────────────────────────────

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $this->bidang === 'PANGAN' ? 'FF2E7D32' : 'FF1565C0'],
                ],
            ],
        ];
    }
}