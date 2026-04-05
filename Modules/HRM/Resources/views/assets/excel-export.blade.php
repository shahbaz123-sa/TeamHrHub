@php
    $assets = $assets ?? [];
    use Carbon\Carbon;
@endphp
<table>
    <thead>
        <tr>
            <th>SR #</th>
            <th>Type</th>
            <th>Name</th>
            <th>Serial No</th>
            <th>Purchase Date</th>
            <th>Assigned To</th>
            <th>Make/Model</th>
        </tr>
    </thead>
    <tbody>
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
                        // fallback to raw string without time part
                        $purchaseDate = preg_replace('/\s+\d{2}:\d{2}:\d{2}(?:\.\d+)?$/', '', (string)$rawDate);
                    }
                }
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ data_get($asset, 'assetType.name') ?? (data_get($asset, 'asset_type.name') ?? '-') }}</td>
                <td>{{ data_get($asset, 'name') }}</td>
                <td>{{ data_get($asset, 'serial_no') }}</td>
                <td>{{ $purchaseDate }}</td>
                <td>{{ data_get($asset, 'employee.name') ?? (data_get($asset, 'employee')['name'] ?? '-') }}</td>
                <td>{{ data_get($asset, 'make_model') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

