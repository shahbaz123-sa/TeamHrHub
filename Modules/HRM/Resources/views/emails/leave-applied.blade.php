<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Leave Application</title>
</head>
<body>
<p>Dear {{ $manager->name }},</p>

<p>
    {{ $leave->employee->name ?? 'An employee' }}{{$leave->employee->employee_code ? ' (' . $leave->employee->employee_code . ')' : ''}} from {{ $leave->employee->department->name ?? '-' }} department has applied for leave.
</p>

<ul>
    <li><strong>Leave Type:</strong>  {{ $leave->leaveType->name ?? '-' }}</li>
    <li><strong>From:</strong> {{ $leave->start_date->format('D, d-m-Y') }}</li>
    <li><strong>To:</strong> {{ $leave->end_date->format('D, d-m-Y') }}</li>
    <li><strong>Days:</strong> {{ $leave->days }}</li>
    <li><strong>Reason:</strong> {{ $leave->leave_reason }}</li>
</ul>

<p>
    Please
    <a href="{{ config('app.url') }}/hrm/leave/list">
        login to the portal
    </a>
    to approve or reject this leave request.
</p>

<p>Regards,<br>{{ config('app.name') }}</p>
</body>
</html>
