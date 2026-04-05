<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Leave Balances</title>
    <style>
        @page { margin: 50px 20px 20px 20px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; margin:0; padding:0 }
        .page-header { position: fixed; top: -40px; left:0; right:0; height: 40px; display:flex; align-items:center }
        .logo img { height: 30px }
        .title { text-align: center; font-weight: bold; font-size: 14px }
        table { width:100%; border-collapse: collapse; margin-top: 10px }
        th, td { border:1px solid #000; padding:6px; text-align:center; }
        th { background:#FADAD3; font-weight:bold; }
        .odd-row { background-color: #FEFAF7; }
        .left { text-align: left; }
    </style>
</head>
<body>
<div class="page-header">
    <div class="logo" style="position:absolute; left:10px;">
        <img src="{{ public_path('images/company-logo.png') }}" alt="Logo">
    </div>
    <div style="width:100%"><div class="title">Leave Balances</div></div>
</div>

<table>
    <thead>
        <tr>
            @foreach($headings as $h)
                <th class="left">{{ $h }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $r)
            @php $rowClass = ($loop->iteration % 2) ? 'odd-row' : ''; @endphp
            <tr class="{{ $rowClass }}">
                @foreach($headings as $h)
                    <td>{{ $r[$h] ?? ($r[strtolower(str_replace(' ', '_', $h))] ?? '-') }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($headings) }}">No records found.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
