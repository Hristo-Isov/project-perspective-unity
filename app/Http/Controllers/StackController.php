<?php

namespace App\Http\Controllers;

use App\Actions\Stack\PopFromStack;
use App\Actions\Stack\PushInStack;
use App\Http\Requests\PushStackRequest;
use App\Http\Resources\StackItemResource;

class StackController extends Controller
{
    public function store(PushStackRequest $request, PushInStack $action): StackItemResource
    {
        return StackItemResource::make($action->handle($request->validated('value')));
    }

    public function destroy(PopFromStack $action): StackItemResource
    {
        $item = $action->handle();

        abort_if($item === null, 404);

        return StackItemResource::make($item);
    }
}