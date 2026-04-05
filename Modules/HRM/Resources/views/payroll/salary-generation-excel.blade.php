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

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Salary Generation Export</title>
</head>
<body>
  <table>
    <tr>
      <th colspan="25" style="text-align:center; font-weight:bold;">
        Salary Generation Report ({{ $formatMonth($month ?? null) }})
      </th>
    </tr>
    <tr>
      <td colspan="25">
        Generated at: {{ $generated_at ?? '' }} |
        Total records: {{ $total_records ?? 0 }} |
        Scope: {{ ucfirst($filters['scope'] ?? 'company') }}
      </td>
    </tr>
  </table>

  <table border="1" cellspacing="0" cellpadding="4">
    <thead>
      <tr>
        <th>#</th>
        <th>Employee</th>
        <th>Department</th>
        <th>Designation</th>
        <th>Salary Month</th>
        <th>Total WD</th>
        <th>On Time</th>
        <th>WFH</th>
        <th>Late</th>
        <th>Short Leave</th>
        <th>Half Leave</th>
        <th>Total Present</th>
        <th>Leave</th>
        <th>Absent</th>
        <th>Not Marked</th>
        <th>Allocated Hours</th>
        <th>Worked Hours</th>
        <th>Salary (Rs)</th>
        <th>Allowances</th>
        <th>Attendance Deduction</th>
        <th>Tax</th>
        <th>Gross Salary</th>
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
        @endphp
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>
            {{ $r['name'] ?? '—' }}
            @if(!empty($r['employee_code']))
              ({{ $r['employee_code'] }})
            @endif
          </td>
          <td>{{ $r['department'] ?? '—' }}</td>
          <td>{{ $r['designation'] ?? '—' }}</td>
          <td>{{ $salaryMonth }}</td>
          <td>{{ $r['total_working_days'] ?? 0 }}</td>
          <td>{{ $r['present'] ?? 0 }}</td>
          <td>{{ $r['wfh'] ?? 0 }}</td>
          <td>{{ $r['late_arrivals'] ?? 0 }}</td>
          <td>{{ $r['short_leave'] ?? 0 }}</td>
          <td>{{ $r['half_day'] ?? 0 }}</td>
          <td>{{ $r['total_present'] ?? 0 }}</td>
          <td>{{ $r['leave'] ?? 0 }}</td>
          <td>{{ $r['absent'] ?? 0 }}</td>
          <td>{{ $r['not_marked'] ?? 0 }}</td>
          <td>{{ $allocatedHours }}</td>
          <td>{{ $workedHours }}</td>
          <td>{{ number_format((float)($r['salary'] ?? 0)) }}</td>
          <td>{{ number_format((float)($r['allowances'] ?? 0)) }}</td>
          <td>{{ number_format((float)($r['attendance_deduction'] ?? 0)) }}</td>
          <td>{{ number_format((float)($r['tax_amount'] ?? 0)) }}</td>
          <td>{{ number_format((float)($r['gross_salary'] ?? 0)) }}</td>
          <td>{{ $hrApproved ? 'Approved' : 'Pending' }}</td>
          <td>{{ $ceoApproved ? 'Approved' : 'Pending' }}</td>
          <td>{{ $statusLabel }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="25" style="text-align:center;">No records found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>

