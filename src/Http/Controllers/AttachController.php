<?php

namespace NovaAttachMany\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;

class AttachController extends Controller
{

    /**
     * @param NovaRequest $request
     * @param $parent
     * @param $relationship
     * @return array
     */
    public function create(NovaRequest $request, $parent, $relationship) : array
    {
        return [
            'available' => $this->getAvailableResources($request, $relationship),
        ];
    }

    /**
     * @param NovaRequest $request
     * @param $parent
     * @param $parentId
     * @param $relationship
     * @return array
     */
    public function edit(NovaRequest $request, $parent, $parentId, $relationship) : array
    {
        return [
            'selected' => $request->findResourceOrFail()->model()->{$relationship}->pluck('id'),
            'available' => $this->getAvailableResources($request, $relationship),
        ];
    }

    /**
     * @param $request
     * @param $relationship
     * @return mixed
     */
    public function getAvailableResources($request, $relationship)
    {
        $resourceClass = $request->newResource();

        $field = $resourceClass
            ->availableFields($request)
            ->where('component', 'nova-attach-many')
            ->where('attribute', $relationship)
            ->first();

        return $field->resourceClass::relatableQuery($request, $field->resourceClass::newModel()->query())->get()
            ->mapInto($field->resourceClass)
            ->filter(static function ($resource) use ($request) {
                return $request->newResource()->authorizedToAttach($request, $resource->resource);
            })->map(static function($resource) {
                return [
                    'display' => $resource->title(),
                    'value' => $resource->getKey(),
                ];
            })->sortBy('display')->values();
    }
}
