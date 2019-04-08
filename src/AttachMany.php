<?php

namespace NovaAttachMany;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Authorizable;
use NovaAttachMany\Rules\ArrayRules;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\ResourceRelationshipGuesser;

class AttachMany extends Field
{

    use Authorizable;

    public $height = '300px';

    public $fullWidth = false;

    public $showToolbar = true;

    public $showCounts = false;

    public $showPreview = false;

    public $showOnIndex = false;

    public $showOnDetail = false;

    public $component = 'nova-attach-many';

    /**
     * AttachMany constructor.
     * @param string $name
     * @param string $attribute
     * @param string $resource
     */
    public function __construct(string $name, string $attribute = null, string $resource = null)
    {
        parent::__construct($name, $attribute);

        $resource = $resource ?? ResourceRelationshipGuesser::guessResource($name);

        $this->resource = $resource;

        $this->resourceClass = $resource;
        $this->resourceName = $resource::uriKey();
        $this->manyToManyRelationship = $this->attribute;

        $this->fillUsing(static function($request, $model, $attribute)
        {
            if ($model instanceof \Illuminate\Database\Eloquent\Model)
            {
                $model::saved(static function($model) use ($attribute, $request)
                {
                    $model->$attribute()->sync(
                        \json_decode($request->$attribute, true)
                    );
                });

                unset($request->$attribute);
            }
        });
    }

    /**
     * @param array|callable|string $rules
     * @return $this|Field
     */
    public function rules($rules)
    {
        $rules = ($rules instanceof Rule || is_string($rules)) ? func_get_args() : $rules;

        $this->rules = [new ArrayRules($rules)];

        return $this;
    }

    /**
     * @param string $resource
     * @param string $attribute
     */
    public function resolve($resource, $attribute = null) : void
    {
        $this->withMeta([
            'height'      => $this->height,
            'fullWidth'   => $this->fullWidth,
            'showCounts'  => $this->showCounts,
            'showPreview' => $this->showPreview,
            'showToolbar' => $this->showToolbar
        ]);
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function authorize(Request $request) : bool
    {
        if (!$this->resourceClass::authorizable())
        {
            return true;
        }

        if (!isset($request->resource))
        {
            return false;
        }

        return \call_user_func([$this->resourceClass, 'authorizedToViewAny'], $request)
               && $request->newResource()->authorizedToAttachAny($request, $this->resourceClass::newModel())
               && parent::authorize($request);
    }

    /**
     * @param $height
     * @return $this
     */
    public function height($height) : self
    {
        $this->height = $height;

        return $this;
    }

    /**
     * @param bool $fullWidth
     * @return $this
     */
    public function fullWidth($fullWidth = true) : self
    {
        $this->fullWidth = $fullWidth;

        return $this;
    }

    /**
     * @return $this
     */
    public function hideToolbar() : self
    {
        $this->showToolbar = false;

        return $this;
    }

    /**
     * @param bool $showCounts
     * @return $this
     */
    public function showCounts($showCounts = true) : self
    {
        $this->showCounts = $showCounts;

        return $this;
    }

    /**
     * @param bool $showPreview
     * @return $this
     */
    public function showPreview($showPreview = true) : self
    {
        $this->showPreview = $showPreview;

        return $this;
    }
}
