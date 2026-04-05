@php
    $histories = $histories ?? [];
@endphp
<table>
    <thead>
        <tr>
            <th>SR #</th>
            <th>Asset</th>
            <th>Serial No</th>
            <th>Employee</th>
            <th>Assigned Date</th>
            <th>Returned At</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($histories as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ data_get($row, 'asset.name') ?? '-' }}</td>
                <td>{{ data_get($row, 'asset.serial_no') ?? '-' }}</td>
                <td>{{ data_get($row, 'employee.name') ?? '-' }}</td>
                <td>{{ data_get($row, 'assigned_date') ?? '-' }}</td>
                <td>{{ data_get($row, 'returned_at') ?? '-' }}</td>
                <td>{{ data_get($row, 'returned_at') ? 'Returned' : 'Assigned' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

