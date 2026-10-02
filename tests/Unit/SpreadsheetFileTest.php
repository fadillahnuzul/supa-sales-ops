<?php

use App\Support\SpreadsheetFile;
use Illuminate\Http\Testing\File;

test('generated Excel files can be read back with semicolon separated cell values intact', function () {
    $rows = [
        ['name', 'materials'],
        ['Racikan lada', 'Lada; Kapulaga'],
    ];
    $binary = SpreadsheetFile::buildXlsx($rows, 'Product Import');
    $file = File::createWithContent('product-import.xlsx', $binary);

    expect(SpreadsheetFile::readRows($file))->toBe($rows);
});

test('CSV files can be read as import rows', function () {
    $file = File::createWithContent(
        'product-import.csv',
        "name,materials\nRacikan lada,\"Lada; Kapulaga\"\n"
    );

    expect(SpreadsheetFile::readRows($file))->toBe([
        ['name', 'materials'],
        ['Racikan lada', 'Lada; Kapulaga'],
    ]);
});
