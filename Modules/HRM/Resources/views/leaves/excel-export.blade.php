<style>
    table.leaves-table { width:100%; border-collapse: collapse }
    table.leaves-table th, table.leaves-table td { border:1px solid #000; padding:6px; text-align:center }
    table.leaves-table th { background:#FADAD3 }
</style>

<div class="title">Leaves Report</div>
<table class="leaves-table">
    <tr>
        <th>SR #</th>
        <th>Emp. Code</th>
        <th>Employee</th>
        <th>Department</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Days</th>
        <th>Type</th>
        <th>Status</th>
        <th>Reason</th>
    </tr>
    @php $sr = 1; @endphp
    @forelse($leaves as $leave)
        <tr>
            <td>{{ $sr++ }}</td>
            <td>{{ $leave->employee->employee_code ?? '-' }}</td>
            <td>{{ $leave->employee->name ?? '-' }}</td>
            <td>{{ $leave->employee->department->name ?? '-' }}</td>
            <td>{{ optional($leave->start_date)->format('d-m-Y') }}</td>
            <td>{{ optional($leave->end_date)->format('d-m-Y') }}</td>
            <td>{{ $leave->days }}</td>
            <td>{{ optional($leave->leaveType)->name ?? optional($leave->leave_type)->name ?? '-' }}</td>
            <td>{{ ucfirst($leave->hr_status ?? $leave->manager_status ?? '-') }}</td>
            <td>{{ $leave->leave_reason ?? '-' }}</td>
        </tr>
    @empty
        <tr><td colspan="10">No leave records found.</td></tr>
    @endforelse
</table>

