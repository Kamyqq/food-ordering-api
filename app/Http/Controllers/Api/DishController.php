<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DishRequests\StoreDishRequest;
use App\Http\Requests\DishRequests\UpdateDishRequest;
use App\Http\Resources\DishResource;
use App\Models\Dish;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DishController extends Controller
{
    use AuthorizesRequests;

    public function index(): ResourceCollection
    {
        return DishResource::collection(Dish::all());
    }

    public function store(StoreDishRequest $request): JsonResponse
    {
        $dish = $request->validated();
        Dish::create($dish);
        return response()->json(['message' => 'Dish created successfully.'], 201);
    }

    public function destroy(Dish $dish): JsonResponse
    {
        $dish->delete();
        return response()->json(['message' => 'Dish deleted successfully.']);
    }

    public function update(UpdateDishRequest $request, Dish $dish): JsonResponse
    {
        $data = $request->validated();
        $dish->update($data);

        return response()->json([
            'message' => 'Dish updated successfully.',
            'dish' => $dish
        ]);
    }

    public function toggleAvailability(Dish $dish): JsonResponse
    {
        $this->authorize('toggleAvailability', $dish);

        $dish->update([
            'is_available' => !$dish->is_available
        ]);

        return response()->json([
            'message' => 'Dish availability successfully.',
            'dish' => $dish
        ]);
    }

    public function restore(Dish $dish): JsonResponse
    {
        $dish->restore();
        $dish->update(['is_available' => false]);
        return response()->json([
            'message' => 'Dish restored successfully.',
            'dish' => $dish
        ]);
    }

    public function showDeleted(): JsonResponse
    {
        return response()->json(Dish::onlyTrashed()->paginate(15));
    }
}
