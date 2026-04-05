<?php

namespace Modules\HRM\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Modules\HRM\Http\Resources\Hr\DashboardResource;
use Modules\HRM\Repositories\Hr\DashboardRepository;

class DashboardController extends Controller
{
    public function __invoke(DashboardRepository $repository)
    {
        return new DashboardResource($repository->getDashboardData());
    }
}
