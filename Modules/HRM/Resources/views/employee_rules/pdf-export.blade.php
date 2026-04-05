<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Rules Report</title>
    <style>
        @page { margin: 50px 20px 20px 20px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; margin:0; padding:0 }
        .page-header { position: fixed; top: -40px; left:0; right:0; height: 40px; display:flex; align-items:center }
        .logo img { height: 30px }
        .title { text-align: center; font-weight: bold; font-size: 14px }
        table { width:100%; border-collapse: collapse; margin-top: 10px }
        th, td { border:1px solid #000; padding:6px; text-align:left; }
        th { background:#FADAD3; font-weight:bold; text-align:center }
        .date-heading { background:#F7B7A1; font-weight:bold; text-align:center }
        .odd-row { background-color: #FEFAF7; }
    </style>
</head>
<body>
@php
    $showTerminationDate = (int) data_get($filters ?? [], 'employment_status_id') != 1;
@endphp
<div class="page-header">
    <div class="logo" style="position:absolute; left:10px;">
        <img src="{{ public_path('images/company-logo.png') }}" alt="Logo">
    </div>
    <div style="width:100%"><div class="title">Employee Rules Report</div></div>
</div>

<table>
    <tr>
        <th>#</th>
        <th>Emp. Code</th>
        <th>Employee</th>
        <th>Department</th>
        <th>Designation</th>
        <th>Employment Type</th>
        @if($showTerminationDate)
            <th>Termination/Resignation Date</th>
        @endif
        <th>Official Email</th>
        <th>Attendance Exemption</th>
    </tr>
    @forelse($employees as $i => $emp)
        @php $rowClass = (($i + 1) % 2) ? 'odd-row' : ''; @endphp
        <tr class="{{ $rowClass }}">
            <td>{{ $i + 1 }}</td>
            <td>{{ $emp->employee_code ?? '-' }}</td>
            <td>{{ $emp->name ?? '-' }}</td>
            <td>{{ $emp->department ?? '-' }}</td>
            <td>{{ $emp->designation ?? '-' }}</td>
            <td>{{ $emp->employement_type ?? '-' }}</td>
            @if($showTerminationDate)
                <td>{{ $emp->termination_effective_date ?? '-' }}</td>
            @endif
            <td>{{ $emp->official_email ?? '-' }}</td>
            <td>{{ $emp->attendance_exemption ? 'Yes' : 'No' }}</td>
        </tr>
    @empty
        <tr><td colspan="{{ $showTerminationDate ? 9 : 8 }}">No employee rules found.</td></tr>
    @endforelse
</table>
</body>
</html>
