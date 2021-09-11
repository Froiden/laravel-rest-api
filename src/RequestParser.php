<?php

namespace Froiden\RestAPI;

use Froiden\RestAPI\Exceptions\Parse\InvalidLimitException;
use Froiden\RestAPI\Exceptions\Parse\InvalidFilterDefinitionException;
use Froiden\RestAPI\Exceptions\Parse\InvalidOrderingDefinitionException;
use Froiden\RestAPI\Exceptions\Parse\MaxLimitException;
use Froiden\RestAPI\Exceptions\Parse\NotAllowedToFilterOnThisFieldException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class RequestParser
{
    /**
     * Checks if fields are specified correctly
     */
    const FIELDS_REGEX = "/([a-zA-Z0-9\\_\\-\\:\\.\\(\\)]+(?'curly'\\{((?>[^{}]+)|(?&curly))*\\})?+)/";

    /**
     * Extracts fields parts
     */
    const FIELD_PARTS_REGEX = "/([^{.]+)(.limit\\(([0-9]+)\\)|.offset\\(([0-9]+)\\)|.order\\(([A-Za-z_]+)\\))*(\\{((?>[^{}]+)|(?R))*\\})?/i";

    /**
     * Checks if filters are correctly specified
     */
    const FILTER_REGEX = "/(\\((?:[\\s]*(?:and|or)?[\\s]*[\\w\\.]+[\\s]+(?:eq|ne|gt|ge|lt|le|lk)[\\s]+(?:\\\"(?:[^\\\"\\\\]|\\\\.)*\\\"|\\d+(,\\d+)*(\\.\\d+(e\\d+)?)?|null)[\\s]*|(?R))*\\))/i";

    /**
     * Extracts filter parts
     */
    const FILTER_PARTS_REGEX = "/([\\w\\.]+)[\\s]+(?:eq|ne|gt|ge|lt|le|lk)[\\s]+(?:\"(?:[^\"\\\\]|\\\\.)*\"|\\d+(?:,\\d+)*(?:\\.\\d+(?:e\\d+)?)?|null)/i";

    /**
     * Checks if ordering is specified correctly
     */
    const ORDER_FILTER = "/[\\s]*([\\w\\.]+)(?:[\\s](?!,))*(asc|desc|)/i";

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
     * Fields to be returned in response. This does not include relations
     *
     * @var array
     */
    private $fields = [];

    /**
     * Relations to be included in the response
     *
     * @var array
     */
    private $relations = [];

    /**
     * Number of results requested per page
     *
     * @var int
     */
    private $limit = 10;

    /**
     * Offset from where fetching should start
     *
     * @var int
     */
    private $offset = 0;

    /**
     * Ordering string
     *
     * @var int
     */
    private $order = null;

    /**
     * Filters to be applied
     *
     * @var string
     */
    private $filters = null;

    /**
     * Attributes passed in request
     *
     * @var array
     */
    private $attributes = [];

    public function __construct($model)
    {
        $this->model = $model;
        $this->primaryKey = call_user_func([new $this->model(), "getKeyName"]);

        $this->parseRequest();
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param array $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return array
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * @param array $relations
     */
    public function setRelations($relations)
    {
        $this->relations = $relations;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return string
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Parse request and fill the parameters
     * @return $this current controller object for chain method calling
     * @throws InvalidFilterDefinitionException
     * @throws InvalidOrderingDefinitionException
     * @throws MaxLimitException
     */
    protected function parseRequest()
    {
        if (request()->limit) {
            if (request()->limit <= 0) {
                throw new InvalidLimitException();
            }
            else if (request()->limit > config("api.maxLimit")) {
                throw new MaxLimitException();
            }
            else {
                $this->limit = request()->limit;
            }
        }
        else {
            $this->limit = config("api.defaultLimit");
        }

        if (request()->offset) {
            $this->offset = request()->offset;
        }
        else {
            $this->offset = 0;
        }

        $this->extractFields();
        $this->extractFilters();
        $this->extractOrdering();
        $this->loadTableName();

        $this->attributes = request()->all();

        return $this;
    }

    protected function extractFields()
    {
        if (request()->fields) {
            $this->parseFields(request()->fields);
        }
        else {
            // Else, by default, we only return default set of visible fields
            $fields = call_user_func($this->model."::getDefaultFields");

            // We parse the default fields in same way as above so that, if
            // relations are included in default fields, they also get included
            $this->parseFields(implode(",", $fields));
        }

        if (!in_array($this->primaryKey, $this->fields)) {
            $this->fields[] = $this->primaryKey;
        }
    }

    protected function extractFilters()
    {
        if (request()->filters) {
            $filters = "(" . request()->filters . ")";

            if (preg_match(RequestParser::FILTER_REGEX, $filters) === 1) {

                preg_match_all(RequestParser::FILTER_PARTS_REGEX, $filters, $parts);

                $filterable = call_user_func($this->model . "::getFilterableFields");

                foreach ($parts[1] as $column) {
                    if (!in_array($column, $filterable)) {
                        throw new NotAllowedToFilterOnThisFieldException("Applying filter on field \"" . $column . "\" is not allowed");
                    }
                }

                // Convert filter name to sql `column` format
                $where = preg_replace(
                    [
                        "/([\\w]+)\\.([\\w]+)[\\s]+(eq|ne|gt|ge|lt|le|lk)/i",
                        "/([\\w]+)[\\s]+(eq|ne|gt|ge|lt|le|lk)/i",
                    ],
                    [
                        "`$1`.`$2` $3",
                        "`$1` $2",
                    ],
                    $filters
                );

                // convert eq null to is null and ne null to is not null
                $where = preg_replace(
                    [
                        "/ne[\\s]+null/i",
                        "/eq[\\s]+null/i"
                    ],
                    [
                        "is not null",
                        "is null"
                    ],
                    $where
                );

                // Replace operators
                $where = preg_replace(
                    [
                        "/[\\s]+eq[\\s]+/i",
                        "/[\\s]+ne[\\s]+/i",
                        "/[\\s]+gt[\\s]+/i",
                        "/[\\s]+ge[\\s]+/i",
                        "/[\\s]+lt[\\s]+/i",
                        "/[\\s]+le[\\s]+/i",
                        "/[\\s]+lk[\\s]+/i"
                    ],
                    [
                        " = ",
                        " != ",
                        " > ",
                        " >= ",
                        " < ",
                        " <= ",
                        " LIKE "
                    ],
                    $where
                );

                $this->filters = $where;
            }
            else {
                throw new InvalidFilterDefinitionException();
            }
        }
    }

    protected function extractOrdering()
    {
        if (request()->order) {
            if (preg_match(RequestParser::ORDER_FILTER, request()->order) === 1) {
                $order = request()->order;


                // eg :  user.name asc, year desc, age,month
                $order = preg_replace(
                    [
                        "/[\\s]*([\\w]+)\\.([\\w]+)(?:[\\s](?!,))*(asc|desc|)/",
                        "/[\\s]*([\\w`\\.]+)(?:[\\s](?!,))*(asc|desc|)/",
                    ],
                    [
                        "$1`.`$2 $3", // Result: user`.`name asc, year desc, age,month
                        "`$1` $2", // Result: `user`.`name` asc, `year` desc, `age`,`month`
                    ],
                    $order
                );

                $this->order = $order;
            }
            else {
                throw new InvalidOrderingDefinitionException();
            }
        }
    }

    /**
     * Recursively parses fields to extract limit, ordering and their own fields
     * and adds width relations
     *
     * @param $fields
     */
    private function parseFields($fields)
    {
        // If fields parameter is set, parse it using regex
        preg_match_all(static::FIELDS_REGEX, $fields, $matches);

        if (!empty($matches[0])) {
            foreach ($matches[0] as $match) {

                preg_match_all(static::FIELD_PARTS_REGEX, $match, $parts);

                $fieldName = $parts[1][0];

                if (Str::contains($fieldName, ":") || call_user_func($this->model . "::relationExists", $fieldName)) {
                    // If field name has a colon, we assume its a relations
                    // OR
                    // If method with field name exists in the class, we assume its a relation
                    // This is default laravel behavior

                    $limit = ($parts[3][0] == "") ? config("api.defaultLimit") : $parts[3][0];
                    $offset = ($parts[4][0] == "") ? 0 : $parts[4][0];
                    $order = ($parts[5][0] == "chronological") ? "chronological" : "reverse_chronological";

                    if (!empty($parts[7][0])) {
                        $subFields = explode(",", $parts[7][0]);
                        // This indicates if user specified fields for relation or not
                        $userSpecifiedFields = true;
                    }
                    else {
                        $subFields = [];
                        $userSpecifiedFields = false;
                    }

                    $fieldName = str_replace(":", ".", $fieldName);

                    if (!isset($this->relations[$fieldName])) {
                        $this->relations[$fieldName] = [
                            "limit" => $limit,
                            "offset" => $offset,
                            "order" => $order,
                            "fields" => $subFields,
                            "userSpecifiedFields" => $userSpecifiedFields
                        ];
                    }
                    else {
                        $this->relations[$fieldName]["limit"] = $limit;
                        $this->relations[$fieldName]["offset"] = $offset;
                        $this->relations[$fieldName]["order"] = $order;
                        $this->relations[$fieldName]["fields"] = array_merge($this->relations[$fieldName]["fields"], $subFields);
                    }

                    // We also need to add the relation's foreign key field to select. If we don't,
                    // relations always return null

                    if (Str::contains($fieldName, ".")) {

                        $relationNameParts = explode('.', $fieldName);
                        $model = $this->model;

                        $relation = null;

                        foreach ($relationNameParts as $rp) {
                            $relation = call_user_func([ new $model(), $rp]);
                            $model = $relation->getRelated();
                        }

                        // Its a multi level relations
                        $fieldParts = explode(".", $fieldName);

                        if ($relation instanceof BelongsTo) {
                            $singular = $relation->getForeignKeyName();
                        }
                        else if ($relation instanceof HasOne || $relation instanceof HasMany) {
                            $singular = $relation->getForeignKeyName();
                        }

                        // Unset last element of array
                        unset($fieldParts[count($fieldParts) - 1]);

                        $parent = implode(".", $fieldParts);

                        if ($relation instanceof HasOne || $relation instanceof HasMany) {
                            // For hasMany and HasOne, the foreign key is in current relation table, not in parent
                            $this->relations[$fieldName]["fields"][] = $singular;
                        }
                        else {
                            // The parent might already been set because we cannot rely on order
                            // in which user sends relations in request
                            if (!isset($this->relations[$parent])) {
                                $this->relations[$parent] = [
                                    "limit" => config("api.defaultLimit"),
                                    "offset" => 0,
                                    "order" => "chronological",
                                    "fields" => isset($singular) ? [$singular] : [],
                                    "userSpecifiedFields" => true
                                ];
                            }
                            else {
                                if (isset($singular)) {
                                    $this->relations[$parent]["fields"][] = $singular;
                                }
                            }
                        }

                        if ($relation instanceof BelongsTo) {
                            $this->relations[$fieldName]["limit"] = max($this->relations[$fieldName]["limit"], $this->relations[$parent]["limit"]);
                        }
                        else if ($relation instanceof HasMany) {
                            $this->relations[$fieldName]["limit"] = $this->relations[$fieldName]["limit"] * $this->relations[$parent]["limit"];
                        }
                    }
                    else {

                        $relation = call_user_func([new $this->model(), $fieldName]);

                        if ($relation instanceof HasOne) {
                            $keyField = explode(".", $relation->getQualifiedParentKeyName())[1];
                        }
                        else if ($relation instanceof BelongsTo) {
                            $keyField = explode(".", $relation->getQualifiedForeignKeyName())[1];
                        }

                        if (isset($keyField) && !in_array($keyField, $this->fields)) {
                            $this->fields[] = $keyField;
                        }

                        if ($relation instanceof BelongsTo) {
                            $this->relations[$fieldName]["limit"] = max($this->relations[$fieldName]["limit"], $this->limit);
                        }
                        else if ($relation instanceof HasMany) {
                            $this->relations[$fieldName]["limit"] = $this->relations[$fieldName]["limit"] * $this->limit;
                        }
                    }

                }
                else {
                    // Else, its a normal field
                    $this->fields[] = $fieldName;
                }
            }
        }
    }

    /**
     * Load table name into the $table property
     */
    private function loadTableName()
    {
        $this->table = call_user_func($this->model."::getTableName");
    }

}
