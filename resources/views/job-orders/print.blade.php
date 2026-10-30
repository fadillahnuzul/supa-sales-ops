<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>JO {{ $jo->so_no }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        body {
            color: #222;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        header {
            align-items: flex-start;
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .company {
            font-size: 17px;
            font-weight: bold;
        }

        .title {
            font-size: 21px;
            font-weight: bold;
            text-align: right;
            text-decoration: underline;
        }

        .subtitle {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
        }

        .top {
            display: flex;
            gap: 6%;
            margin-bottom: 20px;
        }

        .meta {
            border-collapse: collapse;
            width: 43%;
        }

        .meta td {
            border: 1px solid #666;
            padding: 4px;
        }

        .meta td:first-child {
            background: #f3f3f3;
            width: 36%;
        }

        .customer {
            border: 1px solid #666;
            line-height: 1.5;
            padding: 8px;
            white-space: pre-line;
            width: 43%;
        }

        .items {
            border-collapse: collapse;
            width: 100%;
        }

        .items th,
        .items td {
            border: 1px solid #555;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        .items th {
            background: #f2f2f2;
        }

        .items tbody tr {
            height: 58px;
        }

        .delivery {
            font-weight: bold;
            margin-top: 12px;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
            text-align: center;
        }

        .signatures > div {
            width: 29%;
        }

        .signature-space {
            border-bottom: 1px solid #555;
            display: flex;
            height: 55px;
            justify-content: center;
            margin: 0 12px 5px;
        }

        .signature-image {
            height: 55px;
            max-width: 100%;
            object-fit: contain;
        }

        .signer-name {
            font-weight: bold;
            min-height: 14px;
        }

        .toolbar {
            margin-bottom: 12px;
        }

        @media print {
            .toolbar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">Cetak / Simpan PDF</button>
    </div>
    <header>
        <div class="company">SUPA &nbsp; PT. SUPA SURYA NIAGA</div>
        <div>
            <div class="title">PESANAN PENJUALAN</div>
            <div class="subtitle">JOB ORDER</div>
            <p style="text-align: right">Halaman 1 / 1</p>
        </div>
    </header>
    <div class="top">
        <table class="meta">
            <tr>
                <td>Tanggal</td>
                <td>{{ \Carbon\Carbon::parse($jo->date)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td>SO. No</td>
                <td>{{ $jo->so_no }}</td>
            </tr>
            <tr>
                <td>PO. No</td>
                <td>{{ $jo->po_no }}</td>
            </tr>
            <tr>
                <td>No Customer</td>
                <td>{{ $jo->customer_no }}</td>
            </tr>
            <tr>
                <td>PIC Sales</td>
                <td>{{ $jo->pic_name }}</td>
            </tr>
        </table>
        <div class="customer">
            <b>Dipesan</b>
            <hr>
            {{ $jo->customer_name }}<br>
            {{ $jo->customer_address }}
        </div>
    </div>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 10%">KG</th>
                <th style="width: 18%">Kode Barang</th>
                <th style="width: 28%">Nama Barang</th>
                <th style="width: 16%">Packing</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($details as $d)
                <tr>
                    <td>{{ number_format($d->kg, 3, ',', '.') }}</td>
                    <td>{{ $d->product_code }}</td>
                    <td>{{ $d->product_name }}</td>
                    <td>{{ $d->packing }}</td>
                    <td>{{ $d->note }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="delivery">
        Hari/Tanggal Kirim :
        {{ $jo->shipping_date ? \Carbon\Carbon::parse($jo->shipping_date)->format('d/m/Y') : '—' }}
    </div>
    <div class="signatures">
        <div>
            Dibuat Oleh
            <div class="signature-space">
                @if ($signatureImages['created_by'])
                    <img class="signature-image" src="{{ $signatureImages['created_by'] }}" alt="Tanda tangan {{ $signatureNames['created_by'] }}">
                @endif
            </div>
            <div class="signer-name">{{ $signatureNames['created_by'] ?? '' }}</div>
            <div>Marketing Dept/Expor</div>
        </div>
        <div>
            Diperiksa Oleh
            <div class="signature-space">
                @if ($signatureImages['checked_by'])
                    <img class="signature-image" src="{{ $signatureImages['checked_by'] }}" alt="Tanda tangan {{ $signatureNames['checked_by'] }}">
                @endif
            </div>
            <div class="signer-name">{{ $signatureNames['checked_by'] ?? '' }}</div>
            <div>Spv Marketing</div>
        </div>
        <div>
            Disetujui Oleh
            <div class="signature-space">
                @if ($signatureImages['approved_by'])
                    <img class="signature-image" src="{{ $signatureImages['approved_by'] }}" alt="Tanda tangan {{ $signatureNames['approved_by'] }}">
                @endif
            </div>
            <div class="signer-name">{{ $signatureNames['approved_by'] ?? '' }}</div>
            <div>Approving Officer</div>
        </div>
    </div>
</body>
</html>
