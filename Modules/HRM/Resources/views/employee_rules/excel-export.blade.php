<style>
    table.rules-table { width:100%; border-collapse: collapse }
    table.rules-table th, table.rules-table td { border:1px solid #000; padding:6px; text-align:center }
    table.rules-table th { background:#FADAD3 }
</style>

@php
    $showTerminationDate = (int) data_get($filters ?? [], 'employment_status_id') != 1;
@endphp

<div class="title">Employee Rules Report</div>
<table class="rules-table">
    <tr>
        <th>SR #</th>
        <th>Employee</th>
        <th>Emp. Code</th>
        <th>Department</th>
        <th>Designation</th>
        <th>Joining Date</th>
        @if($showTerminationDate)
            <th>Termination/Resignation Date</th>
        @endif
        <th>CNIC</th>
        <th>Employment Type</th>
        <th>Branch</th>
        <th>Official Email</th>
        <th>Personal Email</th>
        <th>Attendance Exemption</th>
    </tr>
    @php $sr = 1; @endphp
    @forelse($employees as $emp)
        <tr>
            <td>{{ $sr++ }}</td>
            <td>{{ $emp->name ?? '-' }}</td>
            <td>{{ $emp->employee_code ?? '-' }}</td>
            <td>{{ $emp->department ?? '-' }}</td>
            <td>{{ $emp->designation ?? '-' }}</td>
            <td>{{ $emp->date_of_joining ?? '-' }}</td>
            @if($showTerminationDate)
                <td>{{ $emp->termination_effective_date ?? '-' }}</td>
            @endif
            <td>{{ $emp->cnic ?? '-' }}</td>
            <td>{{ $emp->employement_type ?? '-' }}</td>
            <td>{{ $emp->branch ?? '-' }}</td>
            <td>{{ $emp->official_email ?? '-' }}</td>
            <td>{{ $emp->personal_email ?? '-' }}</td>
            <td>{{ $emp->attendance_exemption ? 'Yes' : 'No' }}</td>
        </tr>
    @empty
        <tr><td colspan="{{ $showTerminationDate ? 12 : 11 }}">No employee records found.</td></tr>
    @endforelse
</table>
