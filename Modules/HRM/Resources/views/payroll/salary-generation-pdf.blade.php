@php
  use Carbon\Carbon;

  $formatMonth = function ($value) use ($month) {
      $base = $value ?? $month ?? null;
      if (!$base) {
          return '—';
      }

      $clean = substr($base, 0, 7);

      try {
          return Carbon::parse($clean . '-01')->format('F-Y');
      } catch (Exception $e) {
          return $clean;
      }
  };

  $formatMinutes = function ($minutes) {
      $minutes = (int) ($minutes ?? 0);
      $hours = intdiv($minutes, 60);
      $mins = abs($minutes % 60);

      return sprintf('%dh %02dm', $hours, $mins);
  };

  $isApproved = function ($value) {
      $truthy = [true, 1, '1', 'Approved', 'approved', 'APPROVED'];
      return in_array($value, $truthy, true);
  };
@endphp

<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Salary Generation Report</title>
  <style>
    @page { margin: 60px 25px 30px 25px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1d1d1f; margin: 0; padding: 0; }
    .page-header { position: fixed; top: -40px; left: 25px; right: 25px; height: 40px; display: flex; align-items: center; justify-content: center; }
    .logo { position: absolute; left: 25px; }
    .logo img { height: 32px; }
    .title-block { text-align: center; }
    .title-block .report-title { font-size: 16px; font-weight: bold; }
    .title-block .report-subtitle { font-size: 9px; color: #555; }
    .meta { margin-top: 10px; font-size: 9px; color: #555; display: flex; gap: 18px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { border: 1px solid #dce0e6; padding: 5px 6px; }
    th { background-color: #FADAD3; text-transform: uppercase; font-size: 8.5px; text-align: center; }
    td { font-size: 8.5px; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .employee-cell strong { display: block; font-size: 10px; }
    .employee-cell span { display: block; color: #6b7280; font-size: 8px; }
    .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 8px; text-transform: uppercase; }
    .badge.success { background: #E3F4E0; color: #1f7a1f; }
    .badge.warning { background: #FFF4E5; color: #9e6108; }
    .badge.info { background: #E0F2FF; color: #0c5d9c; }
  </style>
</head>
<body style="padding: 20px 0 0 0;">
  <div class="page-header">
    <div class="logo">
      <img src="{{ public_path('images/company-logo.png') }}" alt="Company Logo" />
    </div>
    <div class="title-block">
      <div class="report-title">Salary Generation Report</div>
      <div class="report-subtitle">
        {{ $formatMonth($month ?? null) }}
      </div>
    </div>
  </div>

  <div class="meta">
    <span><strong>Generated:</strong> {{ $generated_at ?? '' }}</span>
    <span><strong>Total Records:</strong> {{ $total_records ?? 0 }}</span>
    <span><strong>Scope:</strong> {{ ucfirst($filters['scope'] ?? 'company') }}</span>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width: 20px;">#</th>
        <th>Employee</th>
        <th>Department</th>
        <th>Designation</th>
        <th>Salary<br />Month</th>
        <th>Total<br />WD</th>
        <th>On Time</th>
        <th>WFH</th>
        <th>Late</th>
        <th>Short<br />Leave</th>
        <th>Half<br />Leave</th>
        <th>Total<br />Present</th>
        <th>Leave</th>
        <th>Absent</th>
        <th>Not<br />Marked</th>
        <th>Allocated<br />Hours</th>
        <th>Worked<br />Hours</th>
        <th class="text-right">Salary (Rs)</th>
        <th class="text-right">Allowances</th>
        <th class="text-right">Attendance<br />Deduction</th>
        <th class="text-right">Tax</th>
        <th class="text-right">Gross Salary</th>
        <th>HR Approval</th>
        <th>CEO Approval</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse(($rows ?? []) as $i => $r)
        @php
          $salaryMonth = $formatMonth($r['salary_month'] ?? $r['month'] ?? null);
          $allocatedHours = $formatMinutes($r['allocated_minutes'] ?? $r['allocatedHours'] ?? 0);
          $workedHours = $formatMinutes($r['worked_minutes'] ?? $r['workedHours'] ?? 0);
          $hrApproved = $isApproved($r['hr_approved'] ?? null);
          $ceoApproved = $isApproved($r['ceo_approved'] ?? null);
          $statusLabel = strtoupper(str_replace('_', ' ', $r['status'] ?? 'generated'));
          $statusClass = match(strtolower($r['status'] ?? 'generated')) {
            'approved' => 'success',
            'hr_approved', 'hr-approved' => 'info',
            'generated' => 'warning',
            default => 'warning',
          };
        @endphp
        <tr>
          <td class="text-center">{{ $i + 1 }}</td>
          <td class="employee-cell">
            <strong>{{ $r['name'] ?? '—' }}</strong>
            <span>{{ $r['employee_code'] ?? '—' }}</span>
          </td>
          <td class="text-center">{{ $r['department'] ?? '—' }}</td>
          <td class="text-center">{{ $r['designation'] ?? '—' }}</td>
          <td class="text-center">{{ $salaryMonth }}</td>
          <td class="text-center">{{ $r['total_working_days'] ?? 0 }}</td>
          <td class="text-center">{{ $r['present'] ?? 0 }}</td>
          <td class="text-center">{{ $r['wfh'] ?? 0 }}</td>
          <td class="text-center">{{ $r['late_arrivals'] ?? 0 }}</td>
          <td class="text-center">{{ $r['short_leave'] ?? 0 }}</td>
          <td class="text-center">{{ $r['half_day'] ?? 0 }}</td>
          <td class="text-center">{{ $r['total_present'] ?? 0 }}</td>
          <td class="text-center">{{ $r['leave'] ?? 0 }}</td>
          <td class="text-center">{{ $r['absent'] ?? 0 }}</td>
          <td class="text-center">{{ $r['not_marked'] ?? 0 }}</td>
          <td class="text-center">{{ $allocatedHours }}</td>
          <td class="text-center">{{ $workedHours }}</td>
          <td class="text-right">{{ number_format((float)($r['salary'] ?? 0)) }}</td>
          <td class="text-right">{{ number_format((float)($r['allowances'] ?? 0)) }}</td>
          <td class="text-right">{{ number_format((float)($r['attendance_deduction'] ?? 0)) }}</td>
          <td class="text-right">{{ number_format((float)($r['tax_amount'] ?? 0)) }}</td>
          <td class="text-right">{{ number_format((float)($r['gross_salary'] ?? 0)) }}</td>
          <td class="text-center">
            <span class="badge {{ $hrApproved ? 'success' : 'warning' }}">{{ $hrApproved ? 'Approved' : 'Pending' }}</span>
          </td>
          <td class="text-center">
            <span class="badge {{ $ceoApproved ? 'success' : 'warning' }}">{{ $ceoApproved ? 'Approved' : 'Pending' }}</span>
          </td>
          <td class="text-center">
            <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="25" class="text-center" style="padding: 18px;">No records found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <script type="text/php">
    if (isset($pdf)) {
        $pdf->page_text(720, 560, "Page {PAGE_NUM} / {PAGE_COUNT}", 'DejaVu Sans', 8, [0.4, 0.4, 0.4]);
    }
  </script>
</body>
</html>

