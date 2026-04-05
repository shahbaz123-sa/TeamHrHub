@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report</title>
    <style>
        @page {
            margin: 50px 20px 20px 20px;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 0;
            color: #000;
        }

        /* Header repeated on every page */
        .page-header {
            position: fixed;
            top: -40px;
            left: 0;
            right: 0;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: flex-start; /* logo at left */
            padding: 0 10px;
        }
        .logo {
            width: 50px; /* fixed width for logo */
        }
        .logo img {
            height: 30px;
        }
        .title-container {
            position: absolute;
            left: 0;
            right: 0;
            text-align: center;
        }
        .title-container .title {
            font-size: 14px;
            font-weight: bold;
        }
        .title-container .date-range {
            font-size: 8px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0px;
        }
        th, td {
            border: 1px solid #000;
            padding: 3px 5px;
            text-align: center;
        }
        th {
            background-color: #FADAD3;
            font-weight: bold;
        }

        /* Status styles */
        .status {
            font-size: 8px;
            font-weight: bold;
            padding: 1px 3px;
            border-radius: 2px;
            text-transform: uppercase;
        }
        .status.Present { background-color: #FFFFFF; color: #28c76f; }
        .status.Absent { background-color: #FFFFFF; color: #dc3545; }
        .status.Late { background-color: #FFFFFF; color: #FF9F43; }
        .status.Leave { background-color: #FFFFFF; color: #00BAD1; }
        .status.Holiday { background-color: #FFFFFF; color: #808390; }
        .status.Not-marked { background-color: #FFFFFF; color: #D55D36; }
        .status.Shift-awaiting { background-color: #FFFFFF; color: #808390; }

        .time-cell { font-family: monospace; }

        /* Date heading */
        .date-heading {
            background-color: #F7B7A1;
            font-weight: bold;
            text-align: center;
            padding: 5px 0;
            font-size: 10px;
        }

        /* Employee columns left-aligned */
        td.employee-name,
        td.employee-code {
            text-align: left;
            padding-left: 5px;
        }
        td.employee-code {
            font-size: 8px;
            color: #555;
        }

        /* Column widths adjusted to include SN and Department */
        th:nth-child(1), td:nth-child(1)   { width: 3%; }  /* S/N */
        th:nth-child(2), td:nth-child(2)   { width: 5%; }  /* Employee Code */
        th:nth-child(3), td:nth-child(3)   { width: 13%; } /* Employee Name */
        th:nth-child(4), td:nth-child(4)   { width: 12%; } /* Department */
        th:nth-child(5), td:nth-child(5)   { width: 5%; }  /* Check In */
        th:nth-child(6), td:nth-child(6)   { width: 5%; }  /* Check In From */
        th:nth-child(7), td:nth-child(7)   { width: 11%; }  /* Check In Location */
        th:nth-child(8), td:nth-child(8)   { width: 5%; }  /* Check Out */
        th:nth-child(9), td:nth-child(9)   { width: 5%; }  /* Check Out From */
        th:nth-child(10), td:nth-child(10) { width: 11%; }  /* Check Out Location */
        th:nth-child(11), td:nth-child(11) { width: 8%; } /* Status */
        th:nth-child(12), td:nth-child(12) { width: 4%; }  /* Late */
        th:nth-child(13), td:nth-child(13) { width: 4%; }  /* Early Leaving */
        th:nth-child(14), td:nth-child(14) { width: 9%; }  /* Overtime */
    </style>
</head>
<body style="padding-top: 10px;">

<!-- Page Header -->
<div class="page-header" style="position: fixed; top: -30px; left: 0; right: 0; height: 40px;">
    <div class="logo" style="position: absolute; left: 0; top: 0;">
        <img src="{{ public_path('images/company-logo.png') }}" alt="Company Logo" style="height:30px;">
    </div>
    <div class="title-container" style="position: absolute; left: 0; right: 0; text-align: center; top: 0;">
        <div class="title" style="font-size:14px; font-weight:bold;">Attendance Report</div>
        <div class="date-range" style="font-size:8px; color:#333;">
            @if(!empty($filters['start_date']) && !empty($filters['end_date']))
                {{ Carbon::parse($filters['start_date'])->isoFormat('dddd, DD-MMM-YYYY') }}
                @if($filters['start_date'] !== $filters['end_date'])
                    <span>to {{ Carbon::parse($filters['end_date'])->isoFormat('dddd, DD-MMM-YYYY') }}</span>
                @endif
            @elseif(!empty($filters['start_date']))
                From {{ Carbon::parse($filters['start_date'])->isoFormat('dddd, DD-MMM-YYYY') }}
            @elseif(!empty($filters['end_date']))
                Up to {{ Carbon::parse($filters['end_date'])->isoFormat('dddd, DD-MMM-YYYY') }}
            @else
                All Dates
            @endif
        </div>
    </div>
</div>

<table>
    @php $currentDate = null; $sn = 0; @endphp

    @forelse($attendances as $attendance)
        @php $attendanceDate = Carbon::parse($attendance['date'])->format('d-m-Y'); @endphp

        @if($attendanceDate !== $currentDate)
            @php $currentDate = $attendanceDate; @endphp
            <tr>
                <td colspan="14" class="date-heading">{{ $currentDate }}</td>
            </tr>
            <tr>
                <th>S/N</th>
                <th>Employee Code</th>
                <th>Employee Name</th>
                <th>Department</th>
                <th>Check In</th>
                <th>Check In From</th>
                <th>Check In Location</th>
                <th>Check Out</th>
                <th>Check Out From</th>
                <th>Check out Location</th>
                <th>Status</th>
                <th>Late</th>
                <th>Early Leaving</th>
                <th>OT</th>
            </tr>
        @endif

        @php $sn++; @endphp
        <tr>
            <td class="sn">{{ $sn }}</td>
            <td class="employee-code">{{ $attendance['employee_code'] ?? '--' }}</td>
            <td class="employee-name">{{ $attendance['employee_name'] ?? 'N/A' }}</td>
            <td class="department">{{ $attendance['department'] ?? '--' }}</td>
            <td class="time-cell">{{ $attendance['check_in'] ? \Carbon\Carbon::parse($attendance['check_in'])->format('h:i A') : '--:--' }}</td>
            <td class="department">{{ $attendance['address_in'] ?? '' }}</td>
            <td class="department">{{ $attendance['location_in'] ?? '' }}</td>
            <td class="time-cell">{{ $attendance['check_out'] ? \Carbon\Carbon::parse($attendance['check_out'])->format('h:i A') : '--:--' }}</td>
            <td class="department">{{ $attendance['address_out'] ?? '' }}</td>
            <td class="department">{{ $attendance['location_out'] ?? '' }}</td>
            <td>
                <span class="status {{ $attendance['status'] ?? 'unknown' }}">{{ ucfirst(str_replace('-', ' ', $attendance['status'] ?? 'unknown')) }}</span>
                @if(
                    in_array($attendance['status'] ?? null, ['Present', 'Late']) &&
                    (str_contains(strtolower($attendance['address_in'] ?? ''), 'home') || str_contains(strtolower($attendance['address_in'] ?? ''), 'wfh'))
                )
                    <small class="text-muted">(WFH)</small>
                @endif
            </td>
            <td class="time-cell">
                @if(isset($attendance['late_minutes']) && $attendance['late_minutes'] > 0)
                    {{ floor($attendance['late_minutes']/60) }}h {{ $attendance['late_minutes']%60 }}m
                @else
                    --
                @endif
            </td>
            <td class="time-cell">
                @if(isset($attendance['early_leaving_minutes']) && $attendance['early_leaving_minutes'] > 0)
                    {{ floor($attendance['early_leaving_minutes']/60) }}h {{ $attendance['early_leaving_minutes']%60 }}m
                @else
                    --
                @endif
            </td>
            <td class="time-cell">
                @if(isset($attendance['overtime_minutes']) && $attendance['overtime_minutes'] > 0)
                    {{ floor($attendance['overtime_minutes']/60) }}h {{ $attendance['overtime_minutes']%60 }}m
                @else
                    --
                @endif
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="14">No attendance records found.</td>
        </tr>
    @endforelse
</table>

</body>
</html>
