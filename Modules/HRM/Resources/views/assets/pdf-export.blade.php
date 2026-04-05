<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Assets Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px; border: 1px solid #ddd; text-align: left; }
        th { background: #FADAD3; }
        .odd { background: #FEFAF7; }
        .title { text-align: center; font-weight: bold; font-size: 16px; margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="title">Assets Report</div>
    <table>
        <thead>
            <tr>
                <th>SR #</th>
                <th>Type</th>
                <th>Name</th>
                <th>Serial No</th>
                <th>Purchase Date</th>
                <th>Assigned To</th>
                <th>Make / Model</th>
            </tr>
        </thead>
        <tbody>
            @php use Carbon\Carbon; @endphp
            @foreach($assets as $i => $asset)
                @php
                    $rawDate = data_get($asset, 'purchase_date') ?? (is_array($asset) && isset($asset['purchase_date']) ? $asset['purchase_date'] : null);
                    $purchaseDate = '-';
                    if ($rawDate) {
                        try {
                            if ($rawDate instanceof \DateTime) {
                                $purchaseDate = Carbon::instance($rawDate)->format('Y-m-d');
                            } else {
                                $purchaseDate = Carbon::parse($rawDate)->format('Y-m-d');
                            }
                        } catch (\Exception $e) {
                            $purchaseDate = preg_replace('/\s+\d{2}:\d{2}:\d{2}(?:\.\d+)?$/', '', (string)$rawDate);
                        }
                    }
                @endphp
                <tr class="{{ $i % 2 ? 'odd' : '' }}">
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $asset['asset_type']['name'] ?? '-' }}</td>
                    <td>{{ $asset['name'] }}</td>
                    <td>{{ $asset['serial_no'] }}</td>
                    <td>{{ $purchaseDate }}</td>
                    <td>{{ $asset['employee']['name'] ?? '-' }}</td>
                    <td>{{ $asset['make_model'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p>Generated at: {{ $generated_at }}</p>
</body>
</html>

