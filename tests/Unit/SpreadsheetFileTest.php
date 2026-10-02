<?php

use App\Http\Controllers\CompetitorController;
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

test('semicolon delimited CSV files are read as separate import columns', function () {
    $file = File::createWithContent(
        'competitor-import.csv',
        "competitor;division;competitor_note;product_code;price;date;product_note\n".
        "Kompetitor;Industri;Pemasok utama;PROD-001;12500;2026-10-02;Harga terbaru\n"
    );

    expect(SpreadsheetFile::readRows($file))->toBe([
        ['competitor', 'division', 'competitor_note', 'product_code', 'price', 'date', 'product_note'],
        ['Kompetitor', 'Industri', 'Pemasok utama', 'PROD-001', '12500', '2026-10-02', 'Harga terbaru'],
    ]);
});

test('competitor import recognizes its template columns after reading a semicolon CSV', function () {
    $file = File::createWithContent(
        'competitor-import.csv',
        "competitor;division;competitor_note;product_code;price;date;product_note\n".
        "Kompetitor;Industri;Pemasok utama;PROD-001;12500;2026-10-02;Harga terbaru\n"
    );
    $normalizeRows = new ReflectionMethod(CompetitorController::class, 'normalizeImportRows');
    $mappedRows = $normalizeRows->invoke(
        new CompetitorController,
        SpreadsheetFile::readRows($file)
    );

    expect($mappedRows)->toBe([
        [
            'competitor' => 'Kompetitor',
            'division' => 'Industri',
            'competitor_note' => 'Pemasok utama',
            'product_code' => 'PROD-001',
            'price' => '12500',
            'date' => '2026-10-02',
            'product_note' => 'Harga terbaru',
        ],
    ]);
});

test('competitor import recognizes all headers from its generated Excel template', function () {
    $headers = [
        'competitor',
        'division',
        'competitor_note',
        'product_code',
        'price',
        'date',
        'product_note',
    ];
    $file = File::createWithContent(
        'competitor-import-template.xlsx',
        SpreadsheetFile::buildXlsx([
            $headers,
            ['Kompetitor', 'Industri', 'Pemasok utama', 'PROD-001', '12500', '2026-10-02', 'Harga terbaru'],
        ], 'Competitor Import')
    );
    $normalizeRows = new ReflectionMethod(CompetitorController::class, 'normalizeImportRows');
    $mappedRows = $normalizeRows->invoke(
        new CompetitorController,
        SpreadsheetFile::readRows($file)
    );

    expect(array_keys($mappedRows[0]))->toBe([
        'competitor',
        'division',
        'competitor_note',
        'product_code',
        'price',
        'date',
        'product_note',
    ]);
});
