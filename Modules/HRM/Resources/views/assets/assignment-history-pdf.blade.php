<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Asset Assignment History</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px; border: 1px solid #ddd; text-align: left; }
        th { background: #E3F2FD; }
        .odd { background: #FAFAFA; }
        .title { text-align: center; font-weight: bold; font-size: 16px; margin-bottom: 8px; }
        .meta { margin-bottom: 10px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="title">Asset Assignment History</div>

    <div class="meta">
        @if(!empty($asset))
            <div><strong>Asset:</strong> {{ $asset->name }} ({{ $asset->serial_no ?? '-' }})</div>
        @else
            <div><strong>Asset:</strong> All Assets</div>
        @endif
        <div><strong>Generated at:</strong> {{ $generated_at }}</div>
        <div><strong>Total records:</strong> {{ $total_records }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>SR #</th>
                @if(empty($asset))
                    <th>Asset</th>
                    <th>Serial No</th>
                @endif
                <th>Employee</th>
                <th>Assigned Date</th>
                <th>Returned At</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php $histories = $histories ?? []; @endphp
            @foreach($histories as $i => $row)
                <tr class="{{ $i % 2 ? 'odd' : '' }}">
                    <td>{{ $i + 1 }}</td>
                    @if(empty($asset))
                        <td>{{ data_get($row, 'asset.name') ?? '-' }}</td>
                        <td>{{ data_get($row, 'asset.serial_no') ?? '-' }}</td>
                    @endif
                    <td>{{ data_get($row, 'employee.name') ?? '-' }}</td>
                    <td>{{ data_get($row, 'assigned_date') ?? '-' }}</td>
                    <td>{{ data_get($row, 'returned_at') ?? '-' }}</td>
                    <td>
                        @if(data_get($row, 'returned_at'))
                            Returned
                        @else
                            Assigned
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

