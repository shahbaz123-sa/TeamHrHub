<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Leave Status Updated</title>
</head>
<body>
<p>Dear {{ $leave->employee->name ?? 'Employee' }},</p>

<p>
    Your leave ({{ $leave->leaveType->name ?? '-' }}) from {{ $leave->start_date->format('D, d-m-Y') }} to {{ $leave->end_date->format('D, d-m-Y') }} has been <strong>{{ $action }}</strong> by {{ $statusType === 'manager' ? 'your manager' : 'HR' }}.
</p>

<p>
    <strong>Days:</strong> {{ $leave->days }}
</p>

@if($statusType === 'manager' && $action === 'approved')
<p>
    Please wait for the approval from the HR manager as well.
</p>
@endif

@if($actor)
<p>
    Action taken by: {{ $actor->name }} ({{ $actor->email }})
</p>
@endif

<p>
    Please
    <a href="{{ config('app.url') }}/hrm/leave/list">login to the portal</a>
    for more details
</p>

<p>Regards,<br>{{ config('app.name') }}</p>
</body>
</html>

