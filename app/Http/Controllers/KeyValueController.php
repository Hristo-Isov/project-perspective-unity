<?php

namespace App\Http\Controllers;

use App\Actions\KeyValue\ForgetKeyValue;
use App\Actions\KeyValue\RetrieveKeyValue;
use App\Actions\KeyValue\StoreKeyValue;
use App\Http\Requests\StoreKeyValueRequest;
use App\Http\Resources\KeyValueItemResource;
use Illuminate\Http\Response;

class KeyValueController extends Controller
{
    public function store(StoreKeyValueRequest $request, StoreKeyValue $action): KeyValueItemResource
    {
        return KeyValueItemResource::make($action->handle($request->validated()));
    }

    public function show(string $key, RetrieveKeyValue $action): KeyValueItemResource
    {
        $item = $action->handle($key);
        
        abort_if($item === null, 404);

        return KeyValueItemResource::make($item);
    }


    public function destroy(string $key, ForgetKeyValue $action): Response
    {
        $action->handle($key);

        return response()->noContent();
    }
}