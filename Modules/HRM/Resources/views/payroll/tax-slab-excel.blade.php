<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Tax Slab Export</title>
</head>
<body>
  <table>
    <tr>
      <th colspan="8" style="text-align:center; font-weight:bold;">Tax Slab Report</th>
    </tr>
    <tr>
      <td colspan="8">
        Generated at: {{ $generated_at ?? '' }} | Total records: {{ $total_records ?? 0 }}
      </td>
    </tr>
  </table>

  <table border="1" cellspacing="0" cellpadding="4">
    <thead>
      <tr>
        <th>#</th>
        <th>Slab Name</th>
        <th>Min Salary (Annual)</th>
        <th>Min Salary (Monthly)</th>
        <th>Max Salary (Annual)</th>
        <th>Max Salary (Monthly)</th>
        <th>Tax Rate (%)</th>
        <th>Fixed Amount (Annual)</th>
        <th>Fixed Amount (Monthly)</th>
        <th>Threshold (Annual)</th>
        <th>Threshold (Monthly)</th>
        <th>Updated At</th>
      </tr>
    </thead>
    <tbody>
      @foreach(($rows ?? []) as $index => $row)
        <tr>
          <td>{{ $index + 1 }}</td>
          <td>{{ $row['name'] ?? '' }}</td>
          <td>{{ number_format((float)($row['min_salary'] ?? 0), 2) }}</td>
          <td>{{ number_format((float)($row['min_salary_monthly'] ?? 0), 2) }}</td>
          <td>{{ isset($row['max_salary']) && $row['max_salary'] !== null ? number_format((float)$row['max_salary'], 2) : '—' }}</td>
          <td>{{ isset($row['max_salary_monthly']) && $row['max_salary_monthly'] !== null ? number_format((float)$row['max_salary_monthly'], 2) : '—' }}</td>
          <td>{{ number_format((float)($row['tax_rate'] ?? 0), 2) }}</td>
          <td>{{ number_format((float)($row['fixed_amount'] ?? 0), 2) }}</td>
          <td>{{ number_format((float)($row['fixed_amount_monthly'] ?? 0), 2) }}</td>
          <td>{{ number_format((float)($row['exceeding_threshold'] ?? 0), 2) }}</td>
          <td>{{ number_format((float)($row['exceeding_threshold_monthly'] ?? 0), 2) }}</td>
          <td>{{ $row['updated_at'] ?? '' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>

