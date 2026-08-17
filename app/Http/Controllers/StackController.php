<?php

namespace App\Http\Controllers;

use App\Actions\Stack\PopFromStack;
use App\Actions\Stack\PushInStack;
use App\Http\Requests\PushStackRequest;
use App\Http\Resources\StackItemResource;
use Illuminate\Http\JsonResponse;

class StackController extends Controller
{
    public function push(PushStackRequest $request, PushInStack $action): JsonResponse
    {
        $item = $action->handle($request->validated('value'));

        return StackItemResource::make($item)
            ->response()
            ->setStatusCode(201);
    }

    public function pop(PopFromStack $action): JsonResponse
    {
        $item = $action->handle();

        if ($item === null) {
            return response()->json(['value' => null])
                ->header('Cache-Control', 'no-store');
        }

        return StackItemResource::make($item)
            ->response()
            ->header('Cache-Control', 'no-store');
    }
}