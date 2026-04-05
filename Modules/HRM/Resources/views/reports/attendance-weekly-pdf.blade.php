@php
  use Carbon\Carbon;
  // Support filters as array or object
  $start = data_get($filters ?? [], 'start_date');
  $end = data_get($filters ?? [], 'end_date');
  $dates = isset($dates) && is_array($dates) ? $dates : (data_get($filters ?? [], 'dates', []) ?: []);
  $employees = $employees ?? [];
  $rangeLabel = '';
  try {
    if ($start && $end) $rangeLabel = Carbon::parse($start)->isoFormat('DD-MMM-YYYY') . ' to ' . Carbon::parse($end)->isoFormat('DD-MMM-YYYY');
    elseif ($start) $rangeLabel = 'From ' . Carbon::parse($start)->isoFormat('DD-MMM-YYYY');
    elseif ($end) $rangeLabel = 'Until ' . Carbon::parse($end)->isoFormat('DD-MMM-YYYY');
  } catch (\Exception $e) { $rangeLabel = trim(($start ?? '') . ' to ' . ($end ?? '')); }

  // Format decimal hours to "Xh Ym"
  $formatHM = function($hours) {
    $totalMinutes = (int) round(((float)($hours ?? 0)) * 60);
    $h = intdiv($totalMinutes, 60);
    $m = $totalMinutes % 60;
    return $h . 'h ' . $m . 'm';
  };
  $formatMH = function($minutes) {
    $minutes = $minutes ?? 0;
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;

    return "{$h}h {$m}m";
  };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Employee Daily Attendance (Range)</title>
  <style>
    @page { margin: 10px 10px; size: A4 landscape; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 7.5px; color: #212529; }
    h1 { font-size: 14px; margin: 0 0 4px 0; color: #3a2d28; }
    .meta { margin: 2px 0 6px; font-size: 9px; color: #6b7280; }
    .divider { height: 1.5px; background: #D55D36; margin: 4px 0 6px; }

    table { border-collapse: collapse; width: 100%; table-layout: auto; }
    th, td { border: 1px solid #000; padding: 2px; text-align: center; box-sizing: border-box; }
    thead th { background: #FADAD3; color: #000000; font-weight: 700; }
    th.sticky { position: sticky; top: 0; }

    .date-head { white-space: pre-line; line-height: 0.85; }

    /* Enforce widths for columns */
    .col-serial { width: 28px !important; max-width: 28px !important; }
    .col-emp { width: 145px !important; max-width: 200px !important; }
    .col-date { width: 85px !important; max-width: 85px !important; }
    .col-pres, .col-abs { width: 37px !important; max-width: 40px !important; }
    .col-leave { width: 35px !important; max-width: 40px !important; }
    .col-work, .col-late, .col-extra { width: 45px !important; max-width: 45px !important; }

    td.emp { text-align: left; }
    th, td { word-wrap: break-word; }

    .header-title {
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 5px;
    }

    /* Status colors to match UI */
    .status-present { color: #28c76f; font-weight: 700; }
    .status-absent { color: #dc3545; font-weight: 700; }
    .status-late { color: #FF9F43; font-weight: 700; }
    .status-half-leave, .status-short-leave, .status-leave { color: #00BAD1; font-weight: 700; }
    .status-holiday, .status-shift-awaiting { color: #808390; font-weight: 700; }
    .status-not-marked { color: #D55D36; font-weight: 700; }
    .status-default { color: #dc3545; font-weight: 700; }

    td.present { background-color: #D6FFE6; }
    td.absent { background-color: #FFCCCC; }
    td.leave { background-color: #DFF3F7; }

    .time { font-size: 8px; }

    /* Department-wise Summary column widths */
    .dept-col-serial { width: 28px !important; max-width: 28px !important; }
    .dept-col-name { width: 220px !important; max-width: 220px !important; }
    .dept-col-other { width: 60px !important; max-width: 60px !important; }
  </style>
</head>
<body>
  <div class="header-title">Employee Weekly Attendance</div>
  <div class="meta">
    <strong>Date Range:</strong> {{ $rangeLabel }}
  </div>
  <div class="divider"></div>

  @if(empty($dates) || empty($employees))
    <p>No data available for the selected range.</p>
  @else
    <table class="main-table">
      <thead>
        <tr>
          <th class="sticky col-serial">S#</th>
          <th class="sticky col-emp">Employee Name</th>
          @foreach($dates as $d)
            @php $dFmt = Carbon::parse($d)->format('l') . "\n" . Carbon::parse($d)->format('d-m-Y'); @endphp
            <th class="sticky date-head col-date">{{ $dFmt }}</th>
          @endforeach
            <th class="sticky col-pres">Present</th>
            <th class="sticky col-leave">Leaves</th>
            <th class="sticky col-abs">Absent</th>
            <th class="sticky col-work">Working<br/>Hours</th>
            <th class="sticky col-late">Late<br/>Hours</th>
            <th class="sticky col-extra">Extra<br/>Hours</th>
        </tr>
      </thead>
      <tbody>
        @foreach($employees as $emp)
          @php
            $empName = is_array($emp) ? ($emp['name'] ?? '') : (data_get($emp, 'name', ''));
            $empCode = is_array($emp) ? ($emp['employee_code'] ?? '') : (data_get($emp, 'employee_code', ''));
            $totalPresent = is_array($emp) ? ($emp['total_present'] ?? 0) : (data_get($emp, 'total_present', 0));
            $totalLeaves = is_array($emp) ? ($emp['total_leaves'] ?? 0) : (data_get($emp, 'total_leaves', 0));
            $totalAbsent = is_array($emp) ? ($emp['total_absent'] ?? 0) : (data_get($emp, 'total_absent', 0));
            $workHours = is_array($emp) ? ($emp['total_working_hours'] ?? 0) : (data_get($emp, 'total_working_hours', 0));
            $lateHours = is_array($emp) ? ($emp['late_hours'] ?? 0) : (data_get($emp, 'late_hours', 0));
            $extraHours = is_array($emp) ? ($emp['extra_working_hours'] ?? 0) : (data_get($emp, 'extra_working_hours', 0));
          @endphp
          <tr>
            <td class="col-serial">{{ $loop->iteration }}</td>
            <td class="emp col-emp">{{ $empName }}</td>
            @foreach($dates as $d)
              @php
                $cell = is_array($emp) ? ($emp['attendance'][$d] ?? null) : data_get($emp, 'attendance.' . $d);
                $status = is_object($cell) ? ($cell->status ?? null) : (is_array($cell) ? ($cell['status'] ?? null) : (is_string($cell) ? $cell : null));
                $checkIn = is_object($cell) ? ($cell->check_in ?? null) : (is_array($cell) ? ($cell['check_in'] ?? null) : null);
                $checkOut = is_object($cell) ? ($cell->check_out ?? null) : (is_array($cell) ? ($cell['check_out'] ?? null) : null);
                $cls = 'status-default';
                $st = strtolower((string)($status ?? '-'));
                if ($st === 'present') $cls = 'status-present';
                elseif ($st === 'absent') $cls = 'status-absent';
                elseif ($st === 'late') $cls = 'status-late';
                elseif ($st === 'half-leave') $cls = 'status-half-leave';
                elseif ($st === 'short-leave') $cls = 'status-short-leave';
                elseif ($st === 'leave') $cls = 'status-leave';
                elseif ($st === 'holiday') $cls = 'status-holiday';
                elseif ($st === 'shift-awaiting') $cls = 'status-shift-awaiting';
                elseif ($st === 'not-marked') $cls = 'status-not-marked';
                $fmt = function($t){ if(empty($t)) return null; try { return Carbon::parse($t)->format('h:i A'); } catch (\Exception $e) { return $t; } };
                $ci = $fmt($checkIn); $co = $fmt($checkOut);
                $cap = $status && is_string($status) ? ucwords(str_replace('-', ' ', $status)) : ($status ?: '-');
              @endphp
              <td class="date-cell col-date">
                <div class="time">
                  @if($ci) {{ $ci }} @endif
                  @if(!$ci && !$co) - @endif
                  @if($co) - {{ $co }} @endif
                </div>
                <div class="{{ $cls }}">{{ $cap }}</div>
              </td>
            @endforeach
              <td class="present col-pres" style="padding:2px;">{{ $totalPresent }}</td>
              <td class="leave col-leave">{{ $totalLeaves }}</td>
              <td class="absent col-abs" style="padding:2px;">{{ $totalAbsent }}</td>
              <td class="working-hours col-work">{{ $formatHM($workHours) }}</td>
              <td class="late-hours col-late">{{ $formatHM($lateHours) }}</td>
              <td class="extra-hours col-extra">{{ $formatHM($extraHours) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  @php
    $deptSummary = $department_summary ?? [];
    $deptGrand = $department_grand ?? [];
  @endphp

  @if(!empty($deptSummary))
    <div style="height: 14px;"></div>
    <h2 style="font-size:14px;margin:0 0 6px 0;color:#3a2d28;">Department-wise Summary</h2>
    <table class="dept-summary" width="100%">
      <colgroup>
        <col width="28" />
        <col width="220" />
        <col width="60" />
        <col width="60" />
        <col width="60" />
        <col width="60" />
        <col width="60" />
        <col width="60" />
        <col width="60" />
        <col width="60" />
        <col width="60" />
        <col width="80" />
      </colgroup>
      <thead>
        <tr>
          <th class="dept-col-serial">S#</th>
          <th class="dept-col-name">Department</th>
          <th class="dept-col-other">Total Employees</th>
          <th class="dept-col-other">Total Present</th>
          <th class="dept-col-other">On Time Present</th>
          <th class="dept-col-other">Late</th>
          <th class="dept-col-other">Absent</th>
          <th class="dept-col-other">Leaves</th>
          <th class="dept-col-other">Half Leaves</th>
          <th class="dept-col-other">Short Leaves</th>
          <th class="dept-col-other">On-Time %</th>
          <th class="dept-col-other">Total Working Hours</th>
        </tr>
      </thead>
      <tbody>
        @foreach($deptSummary as $row)
          @php
            $dep = is_array($row) ? ($row['department'] ?? 'Unassigned') : data_get($row, 'department', 'Unassigned');
            $tot = (int) (is_array($row) ? ($row['total_employees'] ?? 0) : (data_get($row, 'total_employees', 0)));
            $pres = (int) (is_array($row) ? ($row['present_count'] ?? 0) : (data_get($row, 'present_count', 0)));
            $late = (int) (is_array($row) ? ($row['late_count'] ?? 0) : (data_get($row, 'late_count', 0)));
            $totalPres = $pres + $late;
            $abs = (int) (is_array($row) ? ($row['absent_count'] ?? 0) : (data_get($row, 'absent_count', 0)));
            $lev = (int) (is_array($row) ? ($row['leaves'] ?? 0) : (data_get($row, 'leaves', 0)));
            $half = (int) (is_array($row) ? ($row['half_leaves'] ?? 0) : (data_get($row, 'half_leaves', 0)));
            $short = (int) (is_array($row) ? ($row['short_leaves'] ?? 0) : (data_get($row, 'short_leaves', 0)));
            $pct = is_array($row) ? ($row['on_time_percentage'] ?? null) : data_get($row, 'on_time_percentage', null);
            $deptWorkHrs = (float) (is_array($row) ? ($row['total_working_hours'] ?? 0) : (data_get($row, 'total_working_hours', 0)));
            $pctText = $pct === null ? '0.0%' : (number_format((float)$pct, 2) . '%');
          @endphp
          <tr>
            <td class="dept-col-serial">{{ $loop->iteration }}</td>
            <td class="dept-col-name" style="text-align:left;">{{ $dep }}</td>
            <td class="dept-col-other">{{ $tot }}</td>
            <td class="dept-col-other">{{ $totalPres }}</td>
            <td class="dept-col-other present">{{ $pres }}</td>
            <td class="dept-col-other">{{ $late }}</td>
            <td class="dept-col-other absent">{{ $abs }}</td>
            <td class="dept-col-other leave">{{ $lev }}</td>
            <td class="dept-col-other">{{ $half }}</td>
            <td class="dept-col-other">{{ $short }}</td>
            <td class="dept-col-other">{{ $pctText }}</td>
            <td class="dept-col-other">{{ $formatHM($deptWorkHrs) }}</td>
          </tr>
        @endforeach
        @if(!empty($deptGrand))
          @php
            $gtTot = (int) data_get($deptGrand, 'total_employees', 0);
            $gtPres = (int) data_get($deptGrand, 'present_count', 0);
            $gtLate = (int) data_get($deptGrand, 'late_count', 0);
            $gtTotalPres = $gtPres + $gtLate;
            $gtAbs = (int) data_get($deptGrand, 'absent_count', 0);
            $gtLev = (int) data_get($deptGrand, 'leaves', 0);
            $gtHalf = (int) data_get($deptGrand, 'half_leaves', 0);
            $gtShort = (int) data_get($deptGrand, 'short_leaves', 0);
            $gtPct = data_get($deptGrand, 'on_time_percentage', null);
            $gtWorkHrs = (float) data_get($deptGrand, 'working_minutes', 0);
            $gtPctText = $gtPct === null ? '0.0%' : (number_format((float)$gtPct, 2) . '%');
          @endphp
          <tr class="grand-total">
            <td class="dept-col-serial"></td>
            <td class="dept-col-name" style="text-align:left;font-weight:700;">Grand Total</td>
            <td class="dept-col-other" style="font-weight:700;">{{ $gtTot }}</td>
            <td class="dept-col-other" style="font-weight:700;">{{ $gtTotalPres }}</td>
            <td class="dept-col-other present" style="font-weight:700;">{{ $gtPres }}</td>
            <td class="dept-col-other" style="font-weight:700;">{{ $gtLate }}</td>
            <td class="dept-col-other absent" style="font-weight:700;">{{ $gtAbs }}</td>
            <td class="dept-col-other leave" style="font-weight:700;">{{ $gtLev }}</td>
            <td class="dept-col-other" style="font-weight:700;">{{ $gtHalf }}</td>
            <td class="dept-col-other" style="font-weight:700;">{{ $gtShort }}</td>
            <td class="dept-col-other" style="font-weight:700;">{{ $gtPctText }}</td>
            <td class="dept-col-other" style="font-weight:700;">{{ $formatMH($gtWorkHrs) }}</td>
          </tr>
        @endif
      </tbody>
    </table>
  @endif
</body>
</html>

