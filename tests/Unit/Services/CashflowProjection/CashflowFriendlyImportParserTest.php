<?php

namespace Tests\Unit\Services\CashflowProjection;

use App\Services\Modules\CashflowProjection\Import\CashflowFriendlyImportParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CashflowFriendlyImportParserTest extends TestCase
{
    public function test_parse_data_cfc_sheet_from_header_row_three(): void
    {
        $path = $this->makeWorkbook([
            [
                'MAY',
                '26-May-26',
                'HR-02/202605/0016',
                'KASBON MEIDA',
                'PENGAJUAN KASBON MOVIEDAY IN TGL 19 MEI 26',
                '750,000',
                '19-May-26',
                'KAS BON OPERASIONAL',
                'WNS',
            ],
        ]);

        $rows = app(CashflowFriendlyImportParser::class)->parse($path);

        $this->assertCount(1, $rows);
        $this->assertSame(4, $rows[0]['row_number']);
        $this->assertSame('MAY', $rows[0]['bulan']);
        $this->assertSame('2026-05-26', $rows[0]['transaction_date']);
        $this->assertSame('2026-05-19', $rows[0]['due_date']);
        $this->assertSame('HR-02/202605/0016', $rows[0]['no_dokumen']);
        $this->assertSame('KASBON MEIDA', $rows[0]['nama_vendor']);
        $this->assertSame('PENGAJUAN KASBON MOVIEDAY IN TGL 19 MEI 26', $rows[0]['description']);
        $this->assertSame(750000.0, $rows[0]['amount']);
        $this->assertSame('KAS BON OPERASIONAL', $rows[0]['keterangan']);
        $this->assertSame('WNS', $rows[0]['business_unit_code']);
        $this->assertStringContainsString('No Dokumen: HR-02/202605/0016', $rows[0]['notes']);
        $this->assertStringContainsString('Vendor: KASBON MEIDA', $rows[0]['notes']);
    }

    public function test_parse_rejects_missing_data_cfc_sheet(): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->setTitle('Wrong Sheet');
        $path = $this->saveSpreadsheet($spreadsheet);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sheet Data CFC atau Template tidak ditemukan.');

        app(CashflowFriendlyImportParser::class)->parse($path);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function makeWorkbook(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data CFC');
        $sheet->fromArray([
            'BULAN',
            'TGL BAYAR',
            'NO DOKUMEN',
            'NAMA VENDOR',
            'DESKRIPSI',
            'NOMINAL',
            'DUE DATE',
            'KETERANGAN',
            'ENTITAS',
        ], null, 'A3');
        $sheet->fromArray($rows, null, 'A4');

        return $this->saveSpreadsheet($spreadsheet);
    }

    private function saveSpreadsheet(Spreadsheet $spreadsheet): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cashflow-friendly-parser-test');
        if ($path === false) {
            throw new \RuntimeException('Failed to create temp workbook path.');
        }

        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
