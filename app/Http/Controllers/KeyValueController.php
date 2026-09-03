<?php

namespace App\Http\Controllers;

use App\Actions\KeyValue\ForgetKeyValue;
use App\Actions\KeyValue\RetrieveKeyValue;
use App\Actions\KeyValue\StoreKeyValue;
use App\Http\Requests\StoreKeyValueRequest;
use App\Http\Resources\KeyValueItemResource;
use Illuminate\Http\JsonResponse;

class KeyValueController extends Controller
{
    public function store(StoreKeyValueRequest $request, StoreKeyValue $action): JsonResponse
    {
        $item = $action->handle(
            $request->validated('key'),
            $request->validated('value'),
            $request->validated('ttl'),
        );

        $status = $item->wasRecentlyCreated ? 201 : 200;

        return KeyValueItemResource::make($item)
            ->response()
            ->setStatusCode($status);
    }

    public function show(string $key, RetrieveKeyValue $action): JsonResponse
    {
        $item = $action->handle($key);

        if ($item === null) {
            return response()->json(['key' => $key, 'value' => null])
                 ->header('Cache-Control', 'no-store');
        }

        $response = KeyValueItemResource::make($item)->response();

        if ($item->expires_at !== null) {
            $maxAge = max(0, $item->expires_at->getTimestamp() - now()->getTimestamp());
            $response->header('Cache-Control', "max-age={$maxAge}");
        } else {
            $response->header('Cache-Control', 'no-store');
        }

        return $response;
    }


    public function destroy(string $key, ForgetKeyValue $action): JsonResponse
    {
        $action->handle($key);

        return response()->json(null, 204);
    }
}