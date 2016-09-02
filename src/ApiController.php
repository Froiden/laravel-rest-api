<?php

namespace Froiden\RestAPI;

use Froiden\RestAPI\Exceptions\ResourceNotFoundException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Support\Str;
use Froiden\RestAPI\Exceptions\ApiException;
use Froiden\RestAPI\Exceptions\Parse\InvalidFilterDefinitionException;
use Froiden\RestAPI\Exceptions\Parse\InvalidOrderingDefinitionException;
use Froiden\RestAPI\Exceptions\Parse\MaxLimitException;
use Froiden\RestAPI\Exceptions\Parse\NotAllowedToFilterOnThisFieldException;
use Froiden\RestAPI\Exceptions\Parse\UnknownFieldException;

class ApiController extends \Illuminate\Routing\Controller
{

    /**
     * Full class reference to model this controller represents
     *
     * @var string
     */
    protected $model = null;

    /**
     * Table name corresponding to the model this controller is handling
     *
     * @var string
     */

    private $table = null;
    /**
     * Primary key of the model
     *
     * @var string
     */
    private $primaryKey = null;

    /**
     * Default number of records to return
     *
     * @var int
     */
    protected $defaultLimit = 10;

    /**
     * Maximum number of recorded allowed to be returned in single request
     *
     * @var int
     */
    protected $maxLimit = 1000;

    /**
     * Query being built to fetch the results
     *
     * @var Builder
     */
    private $query = null;


    /**
     * Form request to validate index request
     *
     * @var FormRequest
     */
    protected $indexRequest = null;

    /**
     * Form request to validate store request
     *
     * @var FormRequest
     */
    protected $storeRequest = null;

    /**
     * Form request to validate show request
     *
     * @var FormRequest
     */
    protected $showRequest = null;

    /**
     * Form request to validate update request
     *
     * @var FormRequest
     */
    protected $updateRequest = null;

    /**
     * Form request to validate delete request
     *
     * @var FormRequest
     */
    protected $deleteRequest = null;

    /**
     * Time when processing of this request started. Used
     * to measure total processing time
     *
     * @var float
     */
    private $processingStartTime = 0;

    /**
     * Fields to be excluded while saving a request. Fields not in excluded list
     * are considered model attributes
     *
     * @var array
     */
    protected $exclude = ["_token"];

    /**
     * @var RequestParser
     */
    private $parser = null;

    public function __construct()
    {
        $this->processingStartTime = microtime(true);

        $this->primaryKey = call_user_func([new $this->model(), "getKeyName"]);
        $this->table = call_user_func([new $this->model(), "getTable"]);

        if (env("APP_DEBUG") == true) {
            \DB::enableQueryLog();
        }
    }

    /**
     * Process index page request
     *
     * @return mixed
     */
    public function index()
    {
        $this->validate();

        $results = $this->parseRequest()
            ->addIncludes()
            ->addFilters()
            ->addOrdering()
            ->addPaging()
            ->modify()
            ->getResults()
            ->toArray();

        $meta = $this->getMetaData();

        return ApiResponse::make(null, $results, $meta);
    }

    /**
     * Process the show request
     *
     * @return mixed
     */
    public function show($id)
    {
        $this->validate();

        $results = $this->parseRequest()
            ->addIncludes()
            ->addKeyConstraint($id)
            ->modify()
            ->getResults(true)
            ->first()
            ->toArray();

        $meta = $this->getMetaData(true);

        return ApiResponse::make(null, $results, $meta);
    }

    public function store()
    {
        \DB::beginTransaction();

        $this->validate();

        // Create new object
        /** @var ApiModel $object */
        $object = new $this->model();
        $object->fill(request()->all());
        $object->save();

        $meta = $this->getMetaData(true);

        \DB::commit();

        return ApiResponse::make("Resource created successfully", [ "id" => $object->id ], $meta);
    }

    public function update($id)
    {
        \DB::beginTransaction();

        $this->validate();

        // Get object for update
        $this->query = call_user_func($this->model . "::query");
        $this->modify();

        /** @var ApiModel $object */
        $object = $this->query->find($id);

        if (!$object) {
            throw new ResourceNotFoundException();
        }

        $object->fill(request()->all());
        $object->save();

        $meta = $this->getMetaData(true);

        \DB::commit();

        return ApiResponse::make("Resource updated successfully", [ "id" => $object->id ], $meta);
    }

    public function destroy($id)
    {
        \DB::beginTransaction();

        $this->validate();

        // Get object for update
        $this->query = call_user_func($this->model . "::query");
        $this->modify();

        /** @var Model $object */
        $object = $this->query->find($id);

        if (!$object) {
            throw new ResourceNotFoundException();
        }

        $object->delete();

        $meta = $this->getMetaData(true);

        \DB::commit();

        return ApiResponse::make("Resource deleted successfully", null, $meta);
    }

    public function relation($id, $relation)
    {
        $this->validate();

        // To show relations, we just make a new fields parameter, which requests
        // only object id, and the relation and get the results like normal index request

        $fields = "id," . $relation . ".limit(" . ((request()->limit) ? request()->limit : $this->defaultLimit) .
            ")" . ((request()->fields) ? "{" .request()->fields . "}" : "");

        request()->fields = $fields;

        $results = $this->parseRequest()
            ->addIncludes()
            ->addKeyConstraint($id)
            ->modify()
            ->getResults(true)
            ->first()
            ->toArray();

        $data = $results[$relation];

        $meta = $this->getMetaData(true);

        return ApiResponse::make(null, $data, $meta);

    }

    protected function parseRequest()
    {
        $this->parser = new RequestParser($this->model);

        return $this;
    }

    protected function validate()
    {

        if ($this->isIndex()) {
            $requestClass = $this->indexRequest;
        }
        else if ($this->isShow()) {
            $requestClass = $this->showRequest;
        }
        else if ($this->isUpdate()) {
            $requestClass = $this->updateRequest;
        }
        else if ($this->isDelete()) {
            $requestClass = $this->deleteRequest;
        }
        else if ($this->isStore()) {
            $requestClass = $this->storeRequest;
        }
        else if ($this->isRelation()) {
            $requestClass = $this->indexRequest;
        }
        else {
            $requestClass = null;
        }

        // We just make the class, its validation is called automatically
        app()->make($requestClass);
    }

    /**
     * Looks for relations in the requested fields and adds with query for them
     *
     * @return $this current controller object for chain method calling
     */
    protected function addIncludes()
    {
        $relations = $this->parser->getRelations();

        if (!empty($relations)) {
            $includes = [];

            foreach ($relations as $key => $relation) {
                $includes[$key] = function (Relation $q) use ($relation, $key) {

                    $relations = $this->parser->getRelations();

                    $tableName = $q->getRelated()->getTable();
                    $primaryKey = $q->getRelated()->getKeyName();

                    if ($relation["userSpecifiedFields"]) {
                        // Prefix table name so that we do not get ambiguous column errors
                        $fields = $relation["fields"];
                    }
                    else {
                        // Add default fields, if no fields specified
                        $related = $q->getRelated();

                        $fields = call_user_func(get_class($related) . "::getDefaultFields");
                        $fields = array_merge($fields, $relation["fields"]);

                        $relations[$key]["fields"] = $fields;
                    }

                    // Remove appends from select
                    $appends = call_user_func(get_class($q->getRelated()) . "::getAppendFields");
                    $relations[$key]["appends"] = $appends;

                    if (!in_array($primaryKey, $fields)) {
                        $fields[] = $primaryKey;
                    }

                    $fields = array_map(function($name) use($tableName) {
                        return $tableName . "." . $name;
                    }, array_diff($fields, $appends));

                    $q->select($fields);

                    $q->take($relation["limit"]);
                    $q->orderBy($tableName . "." . $primaryKey, ($relation["order"] == "chronological") ? "ASC" : "DESC");

                    $this->parser->setRelations($relations);
                };
            }

            $this->query = call_user_func($this->model."::with", $includes);
        }
        else {
            $this->query = call_user_func($this->model."::query");
        }

        return $this;
    }

    /**
     * Add requested filters. Filters are defined similar to normal SQL queries like
     * (name eq "Milk" or name eq "Eggs") and price lt 2.55
     * The string should be enclosed in double quotes
     * @return $this
     * @throws NotAllowedToFilterOnThisFieldException
     */
    protected function addFilters()
    {
        if ($this->parser->getFilters()) {

            $this->query->whereRaw($this->parser->getFilters());
        }

        return $this;
    }

    /**
     * Add sorting to the query. Sorting is similar to SQL queries
     *
     * @return $this
     */
    protected function addOrdering()
    {
        if ($this->parser->getOrder()) {
            $this->query->orderByRaw($this->parser->getOrder());
        }

        return $this;
    }

    /**
     * Adds paging limit and offset to SQL query
     *
     * @return $this
     */
    protected function addPaging()
    {
        $limit = $this->parser->getLimit();
        $page = $this->parser->getPage();


        if ($page == 1) {
            $skip = 0;
        }
        else {
            $skip = ($page - 1) * $limit;
        }

        $this->query->skip($skip);
        $this->query->take($limit);

        return $this;
    }

    protected function addKeyConstraint($id)
    {
        // Add equality constraint
        $this->query->where($this->table . "." . ($this->primaryKey), "=", $id);

        return $this;
    }

    /**
     * Runs query and fetches results
     *
     * @param bool $single
     * @return Collection
     * @throws ResourceNotFoundException
     */
    protected function getResults($single = false)
    {
        $customAttributes = call_user_func($this->model."::getAppendFields");

        // Laravel's $appends adds attributes always to the output. With this method,
        // we can specify which attributes are to be included
        $appends = [];

        $fields = $this->parser->getFields();

        foreach ($fields as $key => $field) {
            if (in_array($field, $customAttributes)) {
                $appends[] = $field;
                unset($fields[$key]);
            }
        }

        $this->parser->setFields($fields);

        if (!$single) {
            /** @var Collection $results */
            $results = $this->query->select($fields)->get();

        }
        else {
            $results = $this->query->select($fields)->skip(0)->take(1)->get();

            if (!$results) {
                throw new ResourceNotFoundException();
            }
        }

        foreach($results as $result) {
            $result->setAppends($appends);
        }

        $this->processAppends($results);

        return $results;
    }

    private function processAppends($models, $parent = null)
    {
        if (! ($models instanceof Collection)) {
            return $models;
        }
        else if ($models->count() == 0) {
            return $models;
        }

        // Attribute at $key is a relation
        $first = $models->first();
        $attributeKeys = array_keys($first->getRelations());
        $relations = $this->parser->getRelations();

        foreach ($attributeKeys as $key) {
            $relationName = ($parent === null) ? $key : $parent . "." . $key;

            if (isset($relations[$relationName])) {

                $appends = $relations[$relationName]["appends"];
                $appends = array_intersect($appends, $relations[$relationName]["fields"]);

                foreach ($models as $model) {
                    if ($model->$key instanceof Collection) {
                        $model->{$key}->each(function ($item, $key) use($appends) {
                            $item->setAppends($appends);
                        });

                        $this->processAppends($model->$key, $key);
                    }
                    else {
                        $model->$key->setAppends($appends);
                        $this->processAppends(collect($model->$key), $key);
                    }
                }
            }
        }
    }

    /**
     * Builds metadata - paging, links, time to complete request, etc
     *
     * @return array
     */
    protected function getMetaData($single = false)
    {
        if (!$single) {
            $meta = [
                "paging" => [

                ],
                "links" => [

                ]
            ];
            $limit = $this->parser->getLimit();
            $page = $this->parser->getPage();


            $current = $page;

            // Remove offset because setting offset does not return
            // result. As, there is single result in count query,
            // and setting offset will not return that record
            $offset = $this->query->getQuery()->offset;

            $this->query->offset(0);

            $totalRecords = $this->query->count($this->table . "." . $this->primaryKey);

            $this->query->offset($offset);

            $meta["paging"]["total"] = ceil($totalRecords / $limit);

            if ($current < $meta["paging"]["total"]) {
                $meta["paging"]["next"] = $current + 1;
                $meta["links"]["next"] = $this->getNextLink();
            }

            if ($current > 1) {
                $meta["paging"]["previous"] = $current - 1;
                $meta["links"]["previous"] = $this->getPreviousLink();
            }

            $meta["paging"]["current"] = $current * 1;
        }

        $meta["time"] = round(microtime(true) - $this->processingStartTime, 3);

        if (env("APP_DEBUG") == true) {
            $log = \DB::getQueryLog();
            \DB::disableQueryLog();

            $meta["queries"] = count($log);
        }

        return $meta;
    }

    protected function getPreviousLink()
    {
        $current = $this->parser->getPage();

        return request()->url() . "?" .
            trim(
                ((request()->fields) ? "&fields=" . urlencode(request()->fields) : "") .
                ((request()->filters) ? "&filters=" . urlencode(request()->filters) : "") .
                ((request()->order) ? "&fields=" . urlencode(request()->order) : "") .
                "&page=" . ($current - 1),
                "&"
            );
    }

    protected function getNextLink()
    {
        $current = $this->parser->getPage();

        return request()->url() . "?" .
            trim(
                ((request()->fields) ? "&fields=" . urlencode(request()->fields) : "") .
                ((request()->filters) ? "&filters=" . urlencode(request()->filters) : "") .
                ((request()->order) ? "&fields=" . urlencode(request()->order) : "") .
                "&page=" . ($current + 1),
                "&"
            );
    }

    /**
     * Checks if current request is index request
     * @return bool
     */
    protected function isIndex()
    {
        return Str::endsWith(request()->route()->getName(), "index");
    }

    /**
     * Checks if current request is create request
     * @return bool
     */
    protected function isCreate()
    {
        return Str::endsWith(request()->route()->getName(), "create");
    }

    /**
     * Checks if current request is show request
     * @return bool
     */
    protected function isShow()
    {
        return Str::endsWith(request()->route()->getName(), "show");
    }

    /**
     * Checks if current request is update request
     * @return bool
     */
    protected function isUpdate()
    {
        return Str::endsWith(request()->route()->getName(), "update");
    }

    /**
     * Checks if current request is delete request
     * @return bool
     */
    protected function isDelete()
    {
        return Str::endsWith(request()->route()->getName(), "delete");
    }

    /**
     * Checks if current request is store request
     * @return bool
     */
    protected function isStore()
    {
        return Str::endsWith(request()->route()->getName(), "store");
    }
    
    /**
     * Checks if current request is relation request
     * @return bool
     */
    protected function isRelation()
    {
        return Str::endsWith(request()->route()->getName(), "relation");
    }

    /**
     * Calls the modifyRequestType methods to modify query just before execution
     * @return $this
     */
    private function modify()
    {
        if ($this->isIndex()) {
            $this->query = $this->modifyIndex($this->query);
        }
        else if ($this->isShow()) {
            $this->query = $this->modifyShow($this->query);
        }
        else if ($this->isDelete()) {
            $this->query = $this->modifyDelete($this->query);
        }
        else if ($this->isUpdate()) {
            $this->query = $this->modifyUpdate($this->query);
        }

        return $this;
    }

    /**
     * Modify the query for show request
     * @param $query
     * @return mixed
     */
    protected function modifyShow($query)
    {
        return $query;
    }

    /**
     * Modify the query for update request
     * @param $query
     * @return mixed
     */
    protected function modifyUpdate($query)
    {
        return $query;
    }

    /**
     * Modify the query for delete request
     * @param $query
     * @return mixed
     */
    protected function modifyDelete($query)
    {
        return $query;
    }

    /**
     * Modify the query for index request
     * @param $query
     * @return mixed
     */
    protected function modifyIndex($query)
    {
        return $query;
    }

    //endregion
}