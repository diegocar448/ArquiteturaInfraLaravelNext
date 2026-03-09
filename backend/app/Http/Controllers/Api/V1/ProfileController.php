<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StoreProfileRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\DTOs\Profile\CreateProfileDTO;
use App\DTOs\Profile\UpdateProfileDTO;
use App\Actions\Profile\ListProfilesAction;
use App\Actions\Profile\ShowProfileAction;
use App\Actions\Profile\CreateProfileAction;
use App\Actions\Profile\UpdateProfileAction;
use App\Actions\Profile\DeleteProfileAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfileController extends Controller
{
    public function index(ListProfilesAction $action): AnonymousResourceCollection
    {
        $profiles = $action->execute(
            perPage: request()->integer('per_page', 15)
        );

        return ProfileResource::collection($profiles);
    }

    public function store(StoreProfileRequest $request, CreateProfileAction $action): JsonResponse
    {
        $profile = $action->execute(CreateProfileDTO::fromRequest($request));

        return (new ProfileResource($profile))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $profile, ShowProfileAction $action): JsonResponse
    {
        $profile = $action->execute($profile);

        if (!$profile) {
            return response()->json(['message' => 'Perfil nao encontrado.'], 404);
        }

        $profile->load('permissions');

        return response()->json([
            'data' => new ProfileResource($profile),
        ]);
    }

    public function update(UpdateProfileRequest $request, int $profile, UpdateProfileAction $action): JsonResponse
    {
        $updated = $action->execute($profile, UpdateProfileDTO::fromRequest($request));

        if (!$updated) {
            return response()->json(['message' => 'Perfil nao encontrado.'], 404);
        }

        return response()->json([
            'data' => new ProfileResource($updated),
        ]);
    }

    public function destroy(int $profile, DeleteProfileAction $action): JsonResponse
    {
        $deleted = $action->execute($profile);

        if (!$deleted) {
            return response()->json(['message' => 'Perfil nao encontrado.'], 404);
        }

        return response()->json([
            'message' => 'Perfil removido com sucesso.',
        ]);
    }
}