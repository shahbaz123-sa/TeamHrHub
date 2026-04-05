@php
    $formatMH = function($minutes) {
      $minutes = $minutes ?? 0;
      $h = intdiv($minutes, 60);
      $m = $minutes % 60;

      return "{$h}h {$m}m";
    }
@endphp
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Monthly Attendance Summary</title>
  <style>
    @page { size: A4 landscape; margin: 3mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
    h1 { font-size: 18px; margin: 0 0 8px; }
    .meta { margin-bottom: 10px; }
    .meta div { margin: 2px 0; }
    .divider { height: 1.5px; background: #D55D36; margin: 4px 0 6px; }

    table { border-collapse: collapse; width: 100%; table-layout: fixed; }
    th, td { border: 1px solid #000; padding: 2px; text-align: center; box-sizing: border-box; }
    thead th { background: #FADAD3; color: #000000; font-weight: 700; }
    tr:nth-child(even) { background: rgba(213, 93, 54, 0.04); }
    .muted { color: #555; }
    .name { font-weight: 700; font-family: Arial, sans-serif; }

    /* Status cell tints */
    td.present { background-color: #D6FFE6; }
    td.absent { background-color: #FFCCCC; }
    td.leave { background-color: #DFF3F7; }
    /* Darker for even rows */
    tr:nth-child(even) td.present { background-color: rgba(183, 255, 211, 0.8); }
    tr:nth-child(even) td.absent { background-color: rgba(255, 179, 179, 0.8); }
    tr:nth-child(even) td.leave { background-color: rgba(200, 234, 245, 0.8); }

    /* Column widths by index (Sr.#, Name, Dept, Desig, then metrics) */
    thead th:nth-child(1), tbody td:nth-child(1) { width: 3%; }
    thead th:nth-child(2), tbody td:nth-child(2) { width: 14%; text-align: left; }
    thead th:nth-child(3), tbody td:nth-child(3) { width: 10%; }
    thead th:nth-child(4), tbody td:nth-child(4) { width: 10%; }
    /* Metrics 5..12 equal widths */
    thead th:nth-child(5), tbody td:nth-child(5),
    thead th:nth-child(6), tbody td:nth-child(6),
    thead th:nth-child(7), tbody td:nth-child(7),
    thead th:nth-child(8), tbody td:nth-child(8),
    thead th:nth-child(9), tbody td:nth-child(9),
    thead th:nth-child(10), tbody td:nth-child(10),
    thead th:nth-child(11), tbody td:nth-child(11),
    thead th:nth-child(12), tbody td:nth-child(12),
    thead th:nth-child(13), tbody td:nth-child(13) { width: 5.2%; }
    thead th:nth-child(14), tbody td:nth-child(14),
    thead th:nth-child(15), tbody td:nth-child(15) { width: 5.5%; }

    /* Keep multi-line headings compact */
    thead th { line-height: 1.1; white-space: normal; }
  </style>
</head>
<body>
  <h1>Monthly Attendance Summary</h1>
  <div class="meta">
    <div><strong>Month:</strong>
        {{ !empty($month)
            ? \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F-Y')
            : $month
        }}
    </div>
  </div>
  <div class="divider"></div>
  <table>
    <thead>
      <tr>
        <th>Sr.#</th>
        <th>Employee Name &amp; code</th>
        <th class="department">Department</th>
        <th class="designation">Designation</th>
        <th>Total<br/>Working<br/>Days</th>
        <th>On Time</th>
        <th>WFH</th>
        <th>Late<br/>Arrival</th>
        <th>Short<br/>Leave</th>
        <th>Half<br/>Leave</th>
        <th>Total<br/>Present</th>
        <th>Leave</th>
        <th>Absent</th>
        <th>Didn't<br/>Mark</th>
        <th>Allocated<br/>Hours</th>
        <th>Worked<br/>Hours</th>
      </tr>
    </thead>
    <tbody>
      @php $i = 1; @endphp
      @foreach(($rows ?? []) as $r)
        <tr>
          <td>{{ $i++ }}</td>
          <td class="left">
            <div class="name">{{ $r['name'] ?? '-' }}</div>
            @if(!empty($r['employee_code']))
              <div class="muted"><small>Code: {{ $r['employee_code'] }}</small></div>
            @endif
            @if(!empty($r['email']))
              <div class="muted"><small>{{ $r['email'] }}</small></div>
            @endif
          </td>
          <td>{{ $r['department'] ?? '-' }}</td>
          <td>{{ $r['designation'] ?? '-' }}</td>
          <td>{{ (int)($r['total_working_days'] ?? 0) }}</td>
          <td class="present">{{ (int)($r['present'] ?? 0) }}</td>
          <td>{{ (int)($r['wfh'] ?? 0) }}</td>
          <td>{{ (int)($r['late_arrivals'] ?? 0) }}</td>
          <td class="leave">{{ (int)($r['short_leave'] ?? 0) }}</td>
          <td class="leave">{{ (int)($r['half_day'] ?? 0) }}</td>
          <td>{{ (int)($r['total_present'] ?? 0) }}</td>
          <td class="leave">{{ (int)($r['leave'] ?? 0) }}</td>
          <td class="absent">{{ (int)($r['absent'] ?? 0) }}</td>
          <td>{{ (int)($r['not_marked'] ?? 0) }}</td>
          <td>{{ $formatMH((int)($r['allocated_minutes'] ?? 0)) }}</td>
          <td>{{ $formatMH((int)($r['worked_minutes'] ?? 0)) }}</td>
        </tr>
      @endforeach
      @if(empty($rows))
        <tr><td colspan="15">No data</td></tr>
      @endif
    </tbody>
  </table>
</body>
</html>

