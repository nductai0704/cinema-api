<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Http\Requests\RegionRequest;
use App\Http\Resources\RegionResource;

class RegionController extends Controller
{
    /**
     * Display a listing of regions.
     */
    public function index()
    {
        $regions = Region::all();
        return RegionResource::collection($regions);
    }

    /**
     * Store a newly created region.
     */
    public function store(RegionRequest $request)
    {
        $region = Region::create($request->validated());
        return new RegionResource($region);
    }

    /**
     * Update the specified region.
     */
    public function update(RegionRequest $request, Region $region)
    {
        $region->update($request->validated());
        return new RegionResource($region);
    }

    /**
     * Remove the specified region.
     */
    public function destroy(Region $region)
    {
        if ($region->cinemas()->exists()) {
            return response()->json([
                'message' => 'Không thể xóa khu vực này vì đang có rạp trực thuộc.'
            ], 409);
        }

        $region->delete();
        return response()->json(['message' => 'Xóa khu vực thành công.']);
    }
}
