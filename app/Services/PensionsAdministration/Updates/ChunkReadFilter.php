<?php

namespace App\Services\PensionsAdministration\Updates;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{
    public function __construct(
        private int $startRow,
        private int $chunkSize
    ) {
    }

    public function readCell(
        string $columnAddress,
        int $row,
        string $worksheetName = ''
    ): bool {
        if ($row === 1) {
            return true;
        }

        return $row >= $this->startRow
            && $row < ($this->startRow + $this->chunkSize);
    }
}