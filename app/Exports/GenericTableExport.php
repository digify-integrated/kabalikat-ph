<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GenericTableExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected array $columns,
        protected Collection $rows
    ) {}

    public function headings(): array
    {
        // nicer headings: column_name -> Column Name
        return array_map(fn ($c) => ucwords(str_replace('_', ' ', $c)), $this->columns);
    }

    public function collection(): Collection
    {
        // Ensure the export matches the selected columns order
        return $this->rows->map(function ($row) {
            $arr = (array) $row;
            return collect($this->columns)->map(fn ($c) => $arr[$c] ?? null)->all();
        });
    }
}
