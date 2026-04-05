<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Tax Slab Report</title>
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
    th, td { border: 1px solid #dce0e6; padding: 6px 8px; }
    th { background-color: #FADAD3; font-size: 9px; text-transform: uppercase; text-align: center; }
    td { font-size: 9px; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .helper { font-size: 8px; color: #6b7280; display: block; margin-top: 2px; }
  </style>
</head>
<body style="padding: 20px 0 0 0;">
  <div class="page-header">
    <div class="logo">
      <img src="{{ public_path('images/company-logo.png') }}" alt="Company Logo" />
    </div>
    <div class="title-block">
      <div class="report-title">Tax Slab Report</div>
      <div class="report-subtitle">
        {{ ($filters['q'] ?? null) ? 'Filtered search: ' . $filters['q'] : 'All tax slabs' }}
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
        <th style="width: 26px;">#</th>
        <th>Slab Name</th>
        <th>Min Salary (Annual / Monthly)</th>
        <th>Max Salary (Annual / Monthly)</th>
        <th>Tax Rate (%)</th>
        <th>Fixed Amount (Annual / Monthly)</th>
        <th>Threshold (Annual / Monthly)</th>
        <th>Updated At</th>
      </tr>
    </thead>
    <tbody>
      @forelse(($rows ?? []) as $index => $row)
        <tr>
          <td class="text-center">{{ $index + 1 }}</td>
          <td>{{ $row['name'] ?? '—' }}</td>
          <td>
            PKR {{ number_format((float)($row['min_salary'] ?? 0), 2) }}
            <span class="helper">Monthly ≈ {{ number_format((float)($row['min_salary_monthly'] ?? 0), 2) }}</span>
          </td>
          <td>
            @if(isset($row['max_salary']) && $row['max_salary'] !== null)
              PKR {{ number_format((float)$row['max_salary'], 2) }}
              <span class="helper">Monthly ≈ {{ number_format((float)($row['max_salary_monthly'] ?? 0), 2) }}</span>
            @else
              —
            @endif
          </td>
          <td class="text-center">{{ number_format((float)($row['tax_rate'] ?? 0), 2) }}%</td>
          <td>
            PKR {{ number_format((float)($row['fixed_amount'] ?? 0), 2) }}
            <span class="helper">Monthly ≈ {{ number_format((float)($row['fixed_amount_monthly'] ?? 0), 2) }}</span>
          </td>
          <td>
            PKR {{ number_format((float)($row['exceeding_threshold'] ?? 0), 2) }}
            <span class="helper">Monthly ≈ {{ number_format((float)($row['exceeding_threshold_monthly'] ?? 0), 2) }}</span>
          </td>
          <td class="text-center">{{ $row['updated_at'] ?? '—' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="8" class="text-center" style="padding: 18px;">No tax slabs found.</td>
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

