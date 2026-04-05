<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Payroll Export</title>
</head>
<body>
  <table>
    <tr>
      <th colspan="11" style="text-align:center; font-weight:bold;">
        Payroll Report
      </th>
    </tr>
    <tr>
      <td colspan="11">
        Generated at: {{ $generated_at ?? '' }} | Total records: {{ $total_records ?? 0 }}
      </td>
    </tr>
  </table>

  <table>
    <thead>
    <tr>
      <th>#</th>
      <th>Employee Code</th>
      <th>Name</th>
      <th>Department</th>
      <th>Designation</th>
      <th>Employment Type</th>
      <th>Employee Status</th>
      <th>Joining Date</th>
      <th>Phone</th>
      <th>Email</th>
      <th>Salary</th>
      <th>Attendance Deduction</th>
    </tr>
    </thead>
    <tbody>
    @foreach(($rows ?? []) as $i => $row)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $row['employee_code'] ?? '' }}</td>
        <td>{{ $row['name'] ?? '' }}</td>
        <td>{{ $row['department'] ?? '' }}</td>
        <td>{{ $row['designation'] ?? '' }}</td>
        <td>{{ $row['employment_type'] ?? '' }}</td>
        <td>{{ $row['employment_status'] ?? '' }}</td>
        <td>{{ $row['joining_date'] ?? '' }}</td>
        <td>{{ $row['phone'] ?? '' }}</td>
        <td>{{ $row['official_email'] ?? '' }}</td>
        <td>{{ $row['salary'] ?? 0 }}</td>
        <td>{{ $row['attendance_deduction'] ?? 0 }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>
</body>
</html>

