<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaves Report</title>
    <style>
        @page { margin: 50px 20px 20px 20px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; margin:0; padding:0 }
        .page-header { position: fixed; top: -40px; left:0; right:0; height: 40px; display:flex; align-items:center }
        .logo img { height: 30px }
        .title { text-align: center; font-weight: bold; font-size: 14px }
        table { width:100%; border-collapse: collapse; margin-top: 10px }
        th, td { border:1px solid #000; padding:4px; text-align:left; }
        th { background:#FADAD3; font-weight:bold; text-align:center }
        .date-heading { background:#F7B7A1; font-weight:bold; text-align:center }
        .odd-row { background-color: #FEFAF7; }
    </style>
</head>
<body>
<div class="page-header">
    <div class="logo" style="position:absolute; left:10px;">
        <img src="{{ public_path('images/company-logo.png') }}" alt="Logo">
    </div>
    <div style="width:100%"><div class="title">Leaves Report</div></div>
</div>

<table>
    <tr><th>Emp. Code</th><th>Employee</th><th>Department</th><th>Start Date</th><th>End Date</th><th>Days</th><th>Type</th><th>Status</th></tr>
    @forelse($leaves as $leave)
        @php $rowClass = ($loop->iteration % 2) ? 'odd-row' : ''; @endphp
        <tr class="{{ $rowClass }}">
            <td>{{ $leave->employee->employee_code ?? '-' }}</td>
            <td>{{ $leave->employee->name ?? '-' }}</td>
            <td>{{ $leave->employee->department->name ?? '-' }}</td>
            <td>{{ optional($leave->start_date)->format('d-m-Y') }}</td>
            <td>{{ optional($leave->end_date)->format('d-m-Y') }}</td>
            <td>{{ $leave->days }}</td>
            <td>{{ optional($leave->leaveType)->name ?? optional($leave->leave_type)->name ?? '-' }}</td>
            <td>{{ ucfirst($leave->hr_status ?? $leave->manager_status ?? '-') }}</td>
        </tr>
    @empty
        <tr><td colspan="8">No leave records found.</td></tr>
    @endforelse
</table>
</body>
</html>

