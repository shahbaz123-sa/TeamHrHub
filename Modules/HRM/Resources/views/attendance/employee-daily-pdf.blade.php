@php
  use Carbon\Carbon;
  // Compute summary counts from provided $data
  $present = 0; $absent = 0; $late = 0; $leaves = 0;
  $dates = $data['dates'] ?? [];
  $employees = $data['employees'] ?? [];
  foreach ($employees as $emp) {
    foreach ($dates as $d) {
      $s = $emp['attendance'][$d] ?? '';
      if ($s === 'P') $present++;
      elseif ($s === 'A') $absent++;
      elseif ($s === 'LT') $late++;
      elseif (in_array($s, ['L','HL','SL'])) $leaves++;
    }
  }
  $totalCells = max(count($dates) * count($employees), 1);
  $pPct = round(($present / $totalCells) * 100, 1);
  $aPct = round(($absent / $totalCells) * 100, 1);
  $lPct = round(($late / $totalCells) * 100, 1);
  $lvPct = round(($leaves / $totalCells) * 100, 1);

  $monthLabel = '';
    try {
      if (preg_match('/^\d{4}-\d{2}$/', $month)) {
        $monthLabel = Carbon::createFromFormat('Y-m', $month)->format('F Y');
      } else {
        $monthLabel = Carbon::parse($month)->format('F Y');
      }
    } catch (\Exception $e) {
      $monthLabel = $month;
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Attendance Report</title>
    <style>
        @page { margin: 20px 20px; size: A3 landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #212529; }
        h1 {
          font-size: 24px;
          line-height: 1.2;
          font-weight: 800;
          color: #3a2d28;
          margin: 0 0 8px 0;
        }
        .meta {
          margin: 4px 0 12px;
          font-size: 12px;
          color: #6b7280;
        }
        .meta .sep {
          color: #c1c6cd;
          margin: 0 8px;
        }
        .summary { display: flex; gap: 24px; margin: 4px 0 10px; }
        .summary .item { display: inline-flex; align-items: baseline; gap: 16px; font-size: 12px; }
        .summary .label { font-weight: 700; }
        .summary .present { color: #28c76f; }
        .summary .absent { color: #dc3545; }
        .summary .late { color: #FF9F43; }
        .summary .leave { color: #00BAD1; }
        .divider { height: 2px; background: #D55D36; margin: 8px 0 14px; }

        table { border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; text-align: center; }
        th {
          background: #f7f7f7;
          font-weight: 700;
          color: #7a7f85;
        }
        td.employee-name { text-align: left; min-width: 130px; }
        td.code { width: 70px; color: #6b7280; }

        /* Status badges similar to UI (soft pastel rounded) */
        .badge {
          display: inline-block;
          min-width: 15px;
          padding: 4px 6px;
          border-radius: 4px;
          font-weight: 650;
          font-size: 11px;
        }
        .status-P { background: #e9f8ee; color: #28c76f; }
        .status-A { background: #ffe9ee; color: #dc3545; }
        .status-LT { background: #fff4e0; color: #FF9F43; }
        .status-L, .status-HL, .status-SL { background: #DFF3F7; color: #00BAD1; }
        .status-H { background: #eceff4; color: #808390; }
        .status-NM { background: #fde6df; color: #D55D36; }
        .status-SA { background: #eceff4; color: #808390; }

        /* Column widths for days */
        .day-col { width: 28px; }
    </style>
</head>
<body>
    <h1>Monthly Attendance Report</h1>
    <div class="meta">
        <span><strong>Month:</strong> {{ $monthLabel }}</span>
        <span class="sep">|</span>
        <span><strong>Department:</strong> {{ $department ?? 'All Departments' }}</span>
    </div>
    <div class="summary">
        <div class="item present"><span class="label">Present:</span> {{ $present }} ({{ $pPct }}%)</div>
        <div class="item absent"><span class="label">Absent:</span> {{ $absent }} ({{ $aPct }}%)</div>
        <div class="item late"><span class="label">Late:</span> {{ $late }} ({{ $lPct }}%)</div>
        <div class="item leave"><span class="label">Leave:</span> {{ $leaves }} ({{ $lvPct }}%)</div>
    </div>
    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th>Employee Name</th>
                <th>Code</th>
                @foreach($dates as $date)
                    <th class="day-col">{{ Carbon::parse($date)->format('j') }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $employee)
                <tr>
                    <td class="employee-name">{{ $employee['name'] }}</td>
                    <td class="code">{{ $employee['employee_code'] ?? '' }}</td>
                    @foreach($dates as $date)
                        @php $status = $employee['attendance'][$date] ?? ''; @endphp
                        <td>
                            <span class="badge status-{{ $status }}">{{ $status }}</span>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
