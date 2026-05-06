<?php

namespace App\Exports;

use App\Models\Cpcl;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CpclExport implements WithMultipleSheets
{
    use Exportable;

    protected string $role;
    protected ?string $page;    // 'index' | 'terverifikasi' | 'belum' | 'perbaikan' | 'ditolak'
    protected array   $filters; // kecamatan, rencana_usaha, search

    public function __construct(string $role, ?string $page = null, array $filters = [])
    {
        $this->role    = $role;
        $this->page    = $page;
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        $bidangList = $this->getBidangList();

        return collect($bidangList)
            ->map(fn($bidang) => new CpclSheetExport($bidang, $this->page, $this->filters))
            ->all();
    }

    /**
     * Tentukan bidang yang diekspor berdasarkan role.
     * admin        → PANGAN + HARTIBUN (2 sheet)
     * admin_pangan → hanya PANGAN      (1 sheet)
     * admin_hartibun → hanya HARTIBUN  (1 sheet)
     */
    private function getBidangList(): array
    {
        return match ($this->role) {
            'admin'           => ['PANGAN', 'HARTIBUN'],
            'admin_pangan'    => ['PANGAN'],
            'admin_hartibun'  => ['HARTIBUN'],
            default           => [],
        };
    }
}