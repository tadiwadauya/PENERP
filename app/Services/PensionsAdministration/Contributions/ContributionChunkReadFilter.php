<?php

namespace App\Services\PensionsAdministration\Contributions;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ContributionChunkReadFilter implements IReadFilter
{
    public function __construct(
        private readonly int $startRow,
        private readonly int $endRow
    ) {
    }

    public function readCell(
        string $columnAddress,
        int $row,
        string $worksheetName = ''
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | Always Allow Header
        |--------------------------------------------------------------------------
        |
        | We keep row 1 available because ContributionExcelReader needs the
        | headings when normalising the selected chunk.
        |
        */

        if ($row === 1) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Current Chunk
        |--------------------------------------------------------------------------
        */

        return $row >= $this->startRow
            && $row <= $this->endRow;
    }
}