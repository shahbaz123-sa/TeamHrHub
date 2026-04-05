<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payroll Report</title>
  <style>
    @page { margin: 60px 25px 30px 25px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1d1d1f; margin: 0; padding: 0; }
    .page-header { position: fixed; top: -40px; left: 25px; right: 25px; height: 40px; display: flex; align-items: center; justify-content: center; }
    .logo { position: absolute; left: 25px; }
    .logo img { height: 32px; }
    .title-block { display: flex; flex-direction: column; justify-content: center; text-align: center; }
    .title-block .report-title { font-size: 16px; font-weight: bold; }
    .title-block .report-subtitle { font-size: 9px; color: #555; }
    .meta { margin-top: 10px; font-size: 9px; color: #555; display: flex; gap: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { border: 1px solid #dce0e6; padding: 6px 8px; }
    th { background-color: #FADAD3; text-transform: uppercase; font-size: 9px; text-align: left; }
    td { font-size: 9px; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .employee-cell strong { display: block; font-size: 10px; }
    .employee-cell span { display: block; color: #6b7280; font-size: 8px; }
    .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 8px; text-transform: uppercase; }
    .badge.success { background: #E3F4E0; color: #1f7a1f; }
    .badge.warning { background: #FFF4E5; color: #9e6108; }
  </style>
</head>
<body style="padding: 20px 0 0 0;">
  <div class="page-header">
    <div class="logo">
      <img src="{{ public_path('images/company-logo.png') }}" alt="Company Logo" />
    </div>
    <div class="title-block">
      <div class="report-title">Payroll Report</div>
      <div class="report-subtitle">
        {{ ($filters['department_id'] ?? null) ? 'Filtered by department' : 'Organization-wide monthly salary details' }}
      </div>
    </div>
  </div>

  <div class="meta">
    <span><strong>Generated:</strong> {{ $generated_at ?? '' }}</span>
    <span><strong>Total Records:</strong> {{ $total_records ?? 0 }}</span>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width: 30px;">#</th>
        <th>Employee</th>
        <th>Employee Code</th>
        <th>Payroll Type</th>
        <th>Tax Enabled</th>
        <th>Tax Slab</th>
        <th class="text-right">Salary (Rs)</th>
        <th class="text-right">Monthly Tax (Rs)</th>
        <th class="text-right">Net Salary (Rs)</th>
      </tr>
    </thead>
    <tbody>
      @forelse(($rows ?? []) as $i => $row)
        <tr>
          <td class="text-center">{{ $i + 1 }}</td>
          <td class="employee-cell">
            <strong>{{ $row['name'] ?? '—' }}</strong>
            <span>{{ $row['email'] ?? '—' }}</span>
          </td>
          <td>{{ $row['employee_code'] ?? '—' }}</td>
          <td>{{ $row['payroll_type'] ?? '—' }}</td>
          <td>
            @php $taxEnabled = $row['is_tax_applicable'] ?? false; @endphp
            <span class="badge {{ $taxEnabled ? 'success' : 'warning' }}">
              {{ $taxEnabled ? 'Enabled' : 'Disabled' }}
            </span>
          </td>
          <td>{{ $row['tax_slab_name'] ?? '—' }}</td>
          <td class="text-right">{{ number_format((float)($row['salary'] ?? 0)) }}</td>
          <td class="text-right">
            @if($taxEnabled)
              {{ number_format((int)($row['tax_amount'] ?? 0)) }}
            @else
              —
            @endif
          </td>
          <td class="text-right">{{ number_format((float)($row['net_salary'] ?? 0)) }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="9" class="text-center" style="padding: 18px;">No records found.</td>
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

