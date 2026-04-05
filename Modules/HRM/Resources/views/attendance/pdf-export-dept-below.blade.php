@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report - Department Summary Below</title>
    <style>
        @page { margin: 50px 20px 20px 20px; size: landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; margin: 0; padding: 0; color: #000; }
        .page-header { position: fixed; top: -40px; left: 0; right: 0; height: 40px; display: flex; align-items: center; justify-content: flex-start; padding: 0 10px; }
        .logo { width: 50px; }
        .logo img { height: 30px; }
        .title-container { position: absolute; left: 0; right: 0; text-align: center; }
        .title { font-size: 14px; font-weight: bold; }
        .date-range { font-size: 8px; color: #333; }

        table { width: 100%; border-collapse: collapse; margin-top: 0px; }
        th, td { border: 1px solid #000; padding: 3px 5px; text-align: center; }
        th { background-color: #FADAD3; font-weight: bold; }
        .date-heading { background-color: #F7B7A1; font-weight: bold; text-align: center; padding: 5px 0; font-size: 10px; }
        td.employee-name, td.employee-code { text-align: left; padding-left: 5px; }
        td.employee-code { font-size: 8px; color: #555; }
        .time-cell { font-family: monospace; }
        .dept-name { text-align: left; padding-left: 6px; }

        /* Zebra striping for employee rows: light orange on odd rows */
        .row-odd td { background-color: #FFF3E6; }

        /* Status styles (match original Export PDF) */
        .status { font-size: 8px; font-weight: bold; padding: 1px 3px; border-radius: 2px; text-transform: uppercase; }
        .status.Present { color: #28c76f; }
        .status.Absent { color: #dc3545; }
        .status.Late { color: #FF9F43; }
        .status.Leave { color: #00BAD1; }
        .status.Holiday { color: #808390; }
        .status.Not-marked { color: #D55D36; }
        .status.Shift-awaiting { color: #808390; }

        .dept-summary-title {
            font-weight: bold;
            font-size: 10px;
            text-align: left;
            margin: 10px 0 5px 0;
        }
    </style>
</head>
<body style="padding-top: 10px;">

<div class="page-header">
    <div class="logo" style="position:absolute; left:0; top:0;"><img src="{{ public_path('images/company-logo.png') }}" alt="Logo" style="height:30px;" /></div>
    <div class="title-container">
        <div class="title">Attendance Report</div>
        <div class="date-range">
            @if(!empty($filters['start_date']) && !empty($filters['end_date']))
                {{ Carbon::parse($filters['start_date'])->isoFormat('dddd, DD-MMM-YYYY') }}
                @if($filters['start_date'] !== $filters['end_date'])
                    <span>to {{ Carbon::parse($filters['end_date'])->isoFormat('dddd, DD-MMM-YYYY') }}</span>
                @endif
            @else
                All Dates
            @endif
        </div>
    </div>
</div>

<table>
    @php $currentDate = null; $sn = 0; $rowIndexForDate = 0; @endphp

    @foreach($attendances as $index => $attendance)
        @php $attendanceDate = Carbon::parse($attendance['date'])->format('d-m-Y'); @endphp

        @if($attendanceDate !== $currentDate)
            @php $currentDate = $attendanceDate; $sn = 0; $rowIndexForDate = 0; @endphp
            <tr>
                <td colspan="14" class="date-heading">{{ Carbon::parse($currentDate)->isoFormat('dddd, DD-MMM-YYYY') }}</td>
            </tr>
            <tr>
                <th>SR #</th>
                <th>Employee Code</th>
                <th>Employee Name</th>
                <th>Department</th>
                <th>Check In</th>
                <th>Check In From</th>
                <th>Check In Location</th>
                <th>Check Out</th>
                <th>Check Out From</th>
                <th>Check Out Location</th>
                <th>Status</th>
                <th>Late</th>
                <th>Early Leaving</th>
                <th>OT</th>
            </tr>
        @endif

        @php $sn++; $rowIndexForDate++; $isOdd = $rowIndexForDate % 2 === 1; @endphp
        <tr class="{{ !$isOdd ? 'row-odd' : '' }}">
            <td class="sn">{{ $sn }}</td>
            <td class="employee-code">{{ $attendance['employee_code'] ?? '--' }}</td>
            <td class="employee-name">{{ $attendance['employee_name'] ?? 'N/A' }}</td>
            <td class="department">{{ $attendance['department'] ?? '--' }}</td>
            <td class="time-cell">{{ $attendance['check_in'] ? Carbon::parse($attendance['check_in'])->format('h:i A') : '--:--' }}</td>
            <td>{{ $attendance['address_in'] ?? '--' }}</td>
            <td>{{ $attendance['location_in'] ?? '--' }}</td>
            <td class="time-cell">{{ $attendance['check_out'] ? Carbon::parse($attendance['check_out'])->format('h:i A') : '--:--' }}</td>
            <td>{{ $attendance['address_out'] ?? '--' }}</td>
            <td>{{ $attendance['location_out'] ?? '--' }}</td>
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
        @php
            // determine next attendance date to know if this is the last row for this date
            $nextDate = isset($attendances[$index+1]) ? Carbon::parse($attendances[$index+1]['date'])->format('d-m-Y') : null;
        @endphp
        @if($nextDate !== $attendanceDate)
            {{-- after all employee rows for this date, render department-wise summary if available --}}
            @if(!empty($groupedMap[$attendanceDate]['departments']))
                <tr>
                    <td colspan="14" style="padding: 0 4px 2px 5px;">
                        <div class="dept-summary-title">Department wise Summary</div>
                        <table style="width:100%; border-collapse:collapse;">
                            <tr style="background:#FADAD3; font-weight:bold; text-align:center;">
                                <th style="border:1px solid #000; padding:4px;">SR #</th>
                                <th style="border:1px solid #000; padding:4px;">Department</th>
                                <th style="border:1px solid #000; padding:4px;">Total</th>
                                <th style="border:1px solid #000; padding:4px;">On Time</th>
                                <th style="border:1px solid #000; padding:4px;">WFH</th>
                                <th style="border:1px solid #000; padding:4px;">Late</th>
                                <th style="border:1px solid #000; padding:4px;">Half Day</th>
                                <th style="border:1px solid #000; padding:4px;">Leave</th>
                                <th style="border:1px solid #000; padding:4px;">Absent</th>
                                <th style="border:1px solid #000; padding:4px;">Not Marked</th>
                            </tr>
                            @php $srDept = 1; $rowIndexForDate = 0; @endphp
                            @foreach($groupedMap[$attendanceDate]['departments'] as $dept)
                                @php $rowIndexForDate++; $isOdd = $rowIndexForDate % 2 === 1; @endphp
                                <tr class="{{ !$isOdd ? 'row-odd' : '' }}">
                                    <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $srDept++ }}</td>
                                    <td style="border:1px solid #000; padding:4px; text-align:left;">{{ $dept['department'] ?? 'N/A' }}</td>
                                    <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $dept['counts']['Total'] ?? ($dept['counts']['total'] ?? 0) }}</td>
                                    <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $dept['counts']['present'] ?? 0 }}</td>
                                    <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $dept['counts']['WFH'] ?? ($dept['counts']['wfh'] ?? 0) }}</td>
                                    <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $dept['counts']['late'] ?? 0 }}</td>
                                    <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $dept['counts']['Half Day'] ?? ($dept['counts']['half-day'] ?? 0) }}</td>
                                    <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $dept['counts']['leave'] ?? 0 }}</td>
                                    <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $dept['counts']['absent'] ?? 0 }}</td>
                                    <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $dept['counts']['not-marked'] ?? 0 }}</td>
                                </tr>
                            @endforeach
                            @php
                                $grand = [
                                    'total' => 0,
                                    'present' => 0,
                                    'absent' => 0,
                                    'leave' => 0,
                                    'wfh' => 0,
                                    'late' => 0,
                                    'half_day' => 0,
                                    'not_marked' => 0,
                                ];
                                foreach ($groupedMap[$attendanceDate]['departments'] as $dept) {
                                    $c = $dept['counts'] ?? [];
                                    $grand['total'] += $c['Total'] ?? ($c['total'] ?? 0);
                                    $grand['present'] += $c['present'] ?? 0;
                                    $grand['absent'] += $c['absent'] ?? 0;
                                    $grand['leave'] += $c['leave'] ?? 0;
                                    $grand['wfh'] += $c['WFH'] ?? ($c['wfh'] ?? 0);
                                    $grand['late'] += $c['late'] ?? 0;
                                    $grand['half_day'] += $c['Half Day'] ?? ($c['half-day'] ?? 0);
                                    $grand['not_marked'] += $c['not-marked'] ?? 0;
                                }
                            @endphp
                            <tr>
                                <td colspan="2" style="border:1px solid #000; padding:4px; text-align:right; font-weight:bold;">Grand total:</td>
                                <td style="border:1px solid #000; padding:4px; text-align:center; font-weight:bold;">{{ $grand['total'] }}</td>
                                <td style="border:1px solid #000; padding:4px; text-align:center; font-weight:bold;">{{ $grand['present'] }}</td>
                                <td style="border:1px solid #000; padding:4px; text-align:center; font-weight:bold;">{{ $grand['wfh'] }}</td>
                                <td style="border:1px solid #000; padding:4px; text-align:center; font-weight:bold;">{{ $grand['late'] }}</td>
                                <td style="border:1px solid #000; padding:4px; text-align:center; font-weight:bold;">{{ $grand['half_day'] }}</td>
                                <td style="border:1px solid #000; padding:4px; text-align:center; font-weight:bold;">{{ $grand['leave'] }}</td>
                                <td style="border:1px solid #000; padding:4px; text-align:center; font-weight:bold;">{{ $grand['absent'] }}</td>
                                <td style="border:1px solid #000; padding:4px; text-align:center; font-weight:bold;">{{ $grand['not_marked'] }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            @endif
        @endif

    @endforeach

    @if(empty($attendances))
        <tr><td colspan="14">No attendance records found.</td></tr>
    @endif
</table>

</body>
</html>
