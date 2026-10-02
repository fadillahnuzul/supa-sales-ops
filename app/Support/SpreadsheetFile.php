<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

class SpreadsheetFile
{
    public static function readRows(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv' => self::readCsvRows($file->getRealPath()),
            'xlsx' => self::readXlsxRows($file->getRealPath()),
            default => throw new InvalidArgumentException(
                'Format file tidak didukung. Gunakan CSV atau Excel (.xlsx).'
            ),
        };
    }

    public static function buildXlsx(array $rows, string $sheetName): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'sales-ops-xlsx-');

        if ($tempFile === false) {
            throw new RuntimeException('Tidak dapat membuat file Excel sementara.');
        }

        try {
            $zip = new ZipArchive;

            if ($zip->open($tempFile, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
                throw new RuntimeException('Tidak dapat membuat template Excel.');
            }

            $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML);
            $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML);
            $zip->addFromString('xl/workbook.xml', sprintf(<<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="%s" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML, self::escapeXml($sheetName)));
            $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
XML);
            $zip->addFromString('xl/worksheets/sheet1.xml', self::buildWorksheetXml($rows));
            $zip->close();

            $binary = file_get_contents($tempFile);

            if ($binary === false) {
                throw new RuntimeException('Tidak dapat membaca template Excel yang dibuat.');
            }

            return $binary;
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    private static function readCsvRows(string|false $path): array
    {
        if ($path === false) {
            return [];
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [];
        }

        $firstLine = fgets($handle);

        if ($firstLine === false) {
            fclose($handle);

            return [];
        }

        $delimiter = ',';
        $columnCount = 0;

        foreach ([',', ';', "\t"] as $candidate) {
            $columns = str_getcsv($firstLine, $candidate, '"', '');

            if (count($columns) > $columnCount) {
                $delimiter = $candidate;
                $columnCount = count($columns);
            }
        }

        rewind($handle);
        $rows = [];

        while (($row = fgetcsv($handle, separator: $delimiter, escape: '')) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private static function readXlsxRows(string|false $path): array
    {
        if ($path === false) {
            return [];
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return [];
        }

        try {
            $sharedStrings = [];
            $sharedStringXml = $zip->getFromName('xl/sharedStrings.xml');

            if ($sharedStringXml !== false) {
                $shared = simplexml_load_string($sharedStringXml, options: LIBXML_NONET);

                if ($shared !== false) {
                    $namespace = $shared->getNamespaces(true)[''] ?? '';

                    foreach ($shared->children($namespace)->si as $item) {
                        $itemChildren = $item->children($namespace);
                        $text = (string) ($itemChildren->t ?? '');

                        foreach ($itemChildren->r as $run) {
                            $text .= (string) ($run->children($namespace)->t ?? '');
                        }

                        $sharedStrings[] = $text;
                    }
                }
            }

            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        } finally {
            $zip->close();
        }

        if ($sheetXml === false) {
            return [];
        }

        $sheet = simplexml_load_string($sheetXml, options: LIBXML_NONET);

        if ($sheet === false) {
            return [];
        }

        $namespace = $sheet->getNamespaces(true)[''] ?? '';
        $rows = [];

        foreach ($sheet->children($namespace)->sheetData->row as $row) {
            $values = [];

            foreach ($row->children($namespace)->c as $cell) {
                $attributes = $cell->attributes();
                $columnIndex = self::columnNameFromReference((string) ($attributes['r'] ?? ''));
                $type = (string) ($attributes['t'] ?? '');
                $cellChildren = $cell->children($namespace);
                $value = (string) ($cellChildren->v ?? '');

                if ($type === 's' && isset($sharedStrings[(int) $value])) {
                    $value = $sharedStrings[(int) $value];
                }

                if ($type === 'inlineStr' && isset($cellChildren->is)) {
                    $inlineChildren = $cellChildren->is->children($namespace);
                    $value = (string) ($inlineChildren->t ?? '');

                    foreach ($inlineChildren->r as $run) {
                        $value .= (string) ($run->children($namespace)->t ?? '');
                    }
                }

                $values[$columnIndex] = isset($values[$columnIndex])
                    ? $values[$columnIndex].' '.$value
                    : $value;
            }

            $ordered = [];
            $maxColumn = $values === [] ? 0 : max(array_keys($values));

            for ($index = 1; $index <= $maxColumn; $index++) {
                $ordered[] = $values[$index] ?? '';
            }

            $rows[] = $ordered;
        }

        return $rows;
    }

    private static function columnNameFromReference(string $reference): int
    {
        preg_match('/[A-Z]+/', $reference, $matches);

        if (! isset($matches[0])) {
            return 1;
        }

        $value = 0;

        foreach (str_split($matches[0]) as $character) {
            $value = $value * 26 + (ord($character) - 64);
        }

        return $value;
    }

    private static function buildWorksheetXml(array $rows): string
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
XML;

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 1;
            $xml .= '<row r="'.$excelRow.'">';

            foreach ($row as $cellIndex => $value) {
                $column = self::excelColumnName($cellIndex + 1);
                $xml .= '<c r="'.$column.$excelRow.'" t="inlineStr"><is><t xml:space="preserve">'
                    .self::escapeXml((string) $value)
                    .'</t></is></c>';
            }

            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private static function excelColumnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private static function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
