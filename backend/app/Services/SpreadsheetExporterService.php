<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class SpreadsheetExporterService
{
    /**
     * Build a minimal, standards-compliant Excel 2007+ (.xlsx) archive
     * from a header row and a set of data rows.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function toXlsx(array $headers, array $rows, string $sheetName = 'Sheet1'): string
    {
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . htmlspecialchars($sheetName, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '</Relationships>';

        $zip = new ZipArchive;
        $file = tempnam($this->writableTempDirectory(), 'xlsx_');

        if ($file === false || $zip->open($file, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create spreadsheet archive.');
        }

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->buildSheetXml($headers, $rows));
        $zip->close();

        $contents = file_get_contents($file);
        @unlink($file);

        if ($contents === false) {
            throw new RuntimeException('Unable to read generated spreadsheet archive.');
        }

        return $contents;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function buildSheetXml(array $headers, array $rows): string
    {
        $body = '<row r="1">';
        foreach ($headers as $index => $header) {
            $body .= $this->stringCell($this->columnLetter($index + 1).'1', (string) $header);
        }
        $body .= '</row>';

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2;
            $body .= '<row r="'.$rowNumber.'">';

            foreach ($row as $columnIndex => $value) {
                $ref = $this->columnLetter($columnIndex + 1).$rowNumber;

                if (is_int($value) || is_float($value)) {
                    $body .= '<c r="'.$ref.'"><v>'.$value.'</v></c>';
                } else {
                    $body .= $this->stringCell($ref, (string) $value);
                }
            }

            $body .= '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
            . $body
            . '</sheetData></worksheet>';
    }

    private function stringCell(string $ref, string $value): string
    {
        return '<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'
            . htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8')
            . '</t></is></c>';
    }

    private function columnLetter(int $index): string
    {
        $letter = '';

        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }

    private function writableTempDirectory(): string
    {
        $candidates = [
            sys_get_temp_dir(),
            storage_path('app/private'),
            storage_path('framework/cache'),
        ];

        foreach ($candidates as $directory) {
            if (is_dir($directory) && is_writable($directory)) {
                return $directory;
            }
        }

        return sys_get_temp_dir();
    }
}
