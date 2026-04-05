<?php

namespace Modules\HRM\Http\Controllers;

use Modules\HRM\Models\Competency;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Resources\CompetencyResource;
use Modules\HRM\Repositories\CompetencyRepository;
use Modules\HRM\Http\Requests\StoreCompetencyRequest;
use Modules\HRM\Http\Requests\UpdateCompetencyRequest;

class CompetencyController extends Controller
{
    public function index(CompetencyRepository $repo)
    {
        return CompetencyResource::collection($repo->all());
    }

    public function store(StoreCompetencyRequest $request, CompetencyRepository $repo)
    {
        return new CompetencyResource($repo->create($request->validated()));
    }

    public function show($id, CompetencyRepository $repo)
    {
        return new CompetencyResource($repo->find($id));
    }

    public function update(UpdateCompetencyRequest $request, $id, CompetencyRepository $repo)
    {
        return new CompetencyResource($repo->update($id, $request->validated()));
    }

    public function destroy($id, CompetencyRepository $repo)
    {
        $repo->delete($id);
        return response()->noContent();
    }
}
