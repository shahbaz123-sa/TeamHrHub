<?php

namespace Modules\HRM\Helpers;

use Modules\HRM\Models\Branch;

class AttendanceHelper
{
    public static function isNearbyOffice($empLat, $empLng, $accuracy, Branch $empBranch)
    {
        $earthRadius = 6371000;

        $radius = data_get($empBranch, 'attendance_radius', config('office.radius_meter'));
        $officeLat = data_get($empBranch, 'latitude', config('office.latitude'));
        $officeLng = data_get($empBranch, 'longitude', config('office.longitude'));

        $dLat = deg2rad($officeLat - $empLat);
        $dLng = deg2rad($officeLng - $empLng);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($empLat)) * cos(deg2rad($officeLat)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return ($distance - $accuracy) <= $radius;
    }
}
