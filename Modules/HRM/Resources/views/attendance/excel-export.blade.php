<style>
    .attendance-title {
        font-weight: 700;
        font-size: 18px;
        margin: 0 0 10px 0;
    }

    table.attendance-table {
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        margin-bottom: 0;
    }

    table.attendance-table th,
    table.attendance-table td {
        border: 1px solid #000;
        padding: 6px;
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: anywhere;
        line-height: 1.25;
        font-size: 12px;
        text-align: center;
        vertical-align: middle;
    }

    /* Colors */
    .date-heading {
        background-color: #D55D36;
        color: #fff;
        font-weight: 700;
        text-align: center;
    }

    .head-bg {
        background-color: #FADAD3;
        font-weight: 700;
    }

    .summary-val {
        background-color: #F7B7A1;
        font-weight: 700;
    }

    .cell-count {
        font-weight: 700;
        display: block;
        text-align: center;
    }

    .cell-names {
        display: block;
        text-align: center;
        white-space: pre-line; /* helps HTML, XLSX uses wrapText from export */
    }
</style>

<div class="attendance-title">Daily Attendance Report</div>

@forelse($attendances as $attendanceDateGroup)

    @php
        $dateFormatted = \Carbon\Carbon::parse($attendanceDateGroup['date'])->format('d-M-Y');

        $dateTotals = [
            'total' => 0,
            'present' => 0,
            'absent' => 0,
            'leave' => 0,
            'WFH' => 0,
            'late' => 0,
            'Half Day' => 0,
            'not-marked' => 0,
        ];

        foreach(($attendanceDateGroup['departments'] ?? []) as $d){
            $p  = $d['counts']['present'] ?? 0;
            $a  = $d['counts']['absent'] ?? 0;
            $l  = $d['counts']['leave'] ?? 0;
            $w  = $d['counts']['WFH'] ?? 0;
            $lt = $d['counts']['late'] ?? 0;
            $hd = $d['counts']['Half Day'] ?? 0;
            $nm = $d['counts']['not-marked'] ?? 0;

            $deptTotal = $p + $a + $l + $w + $lt + $hd + $nm;

            $dateTotals['total']      += $deptTotal;
            $dateTotals['present']    += $p;
            $dateTotals['absent']     += $a;
            $dateTotals['leave']      += $l;
            $dateTotals['WFH']        += $w;
            $dateTotals['late']       += $lt;
            $dateTotals['Half Day']   += $hd;
            $dateTotals['not-marked'] += $nm;
        }

        $printNames = function ($arr) {
            if (empty($arr) || !is_array($arr)) return '';
            return '<br>' . implode(', ', $arr);
        };

    @endphp

    <table class="attendance-table">
        {{-- Date Row --}}
        <tr>
            <td colspan="10" class="date-heading">Date: {{ $dateFormatted }}</td>
        </tr>

        {{-- Summary Header Row --}}
        <tr class="head-bg">
            <th colspan="3">Total Employees</th>
            <th>Present</th>
            <th>Absent</th>
            <th>Leave</th>
            <th>WFH</th>
            <th>Late</th>
            <th>Half Day</th>
            <th>Not Marked</th>
        </tr>

        {{-- Summary Values Row --}}
        <tr class="summary-val">
            <td colspan="3">{{ $dateTotals['total'] }}</td>
            <td>{{ $dateTotals['present'] }}</td>
            <td>{{ $dateTotals['absent'] }}</td>
            <td>{{ $dateTotals['leave'] }}</td>
            <td>{{ $dateTotals['WFH'] }}</td>
            <td>{{ $dateTotals['late'] }}</td>
            <td>{{ $dateTotals['Half Day'] }}</td>
            <td>{{ $dateTotals['not-marked'] }}</td>
        </tr>

        {{-- Department Table Header --}}
        <tr class="head-bg">
            <th>SR #</th>
            <th>Department</th>
            <th>Total</th>
            <th>Present</th>
            <th>Absent</th>
            <th>Leave</th>
            <th>WFH</th>
            <th>Late</th>
            <th>Half Day</th>
            <th>Not Marked</th>
        </tr>

        {{-- Department Rows --}}
        @php $sr = 1; @endphp
        @forelse(($attendanceDateGroup['departments'] ?? []) as $department)

            @php
                $p  = $department['counts']['present'] ?? 0;
                $a  = $department['counts']['absent'] ?? 0;
                $l  = $department['counts']['leave'] ?? 0;
                $w  = $department['counts']['WFH'] ?? 0;
                $lt = $department['counts']['late'] ?? 0;
                $hd = $department['counts']['Half Day'] ?? 0;
                $nm = $department['counts']['not-marked'] ?? 0;

                $deptTotal = $p + $a + $l + $w + $lt + $hd + $nm;
            @endphp

            <tr>
                <td>{{ $sr++ }}</td>
                <td>{{ $department['department'] ?? 'N/A' }}</td>
                <td><span class="cell-count">{{ $deptTotal }}</span></td>

                @foreach(['present','absent','leave','WFH','late','Half Day','not-marked'] as $key)
                    <td>
                        <span class="cell-count">{{ $department['counts'][$key] ?? 0 }}</span>
                        @if(!empty($department['employees'][$key]))
                            <span class="cell-names">{!! $printNames($department['employees'][$key]) !!}</span>
                        @endif
                    </td>
                @endforeach
            </tr>

        @empty
            <tr>
                <td colspan="10">No departments found for this date.</td>
            </tr>
        @endforelse

    </table>

@empty
    <table class="attendance-table">
        <tr><td>No attendance records found.</td></tr>
    </table>
@endforelse
