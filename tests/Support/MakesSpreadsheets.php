<?php

namespace Tests\Support;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait MakesSpreadsheets
{
    protected function makeXlsx(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'tst');
        (new Xlsx($spreadsheet))->save($path);
        $content = (string) file_get_contents($path);
        unlink($path);

        return $content;
    }

    protected function readXlsx(string $content): array
    {
        $path = tempnam(sys_get_temp_dir(), 'tst');
        file_put_contents($path, $content);
        $rows = IOFactory::load($path, 0, [IOFactory::READER_XLSX])->getActiveSheet()->toArray(null, true, true, false);
        unlink($path);

        return $rows;
    }
}
