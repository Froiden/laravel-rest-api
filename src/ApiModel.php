<?php namespace Froiden\RestAPI;

use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException;
use Froiden\RestAPI\Exceptions\ResourceNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;

class ApiModel extends Model
{

    /**
     * List of fields that are visible by default. Only these fields
     * are selected and returned from database
     *
     * @var array
     */
    protected $default = ["id"];

    /**
     * List of fields that are always hidden. Unless modified, these
     * fields are not visible when object is serialised. To comply with
     * Rest architecture, its recommended to hide all relation fields
     * (like, user_id, student_id)
     *
     * @var array
     */
    protected $hidden = ["created_at", "updated_at", "pivot"];

    /**
     * List of fields on which filters are allowed to be applied. For security
     * reasons we cannot allow filters to be allowed on arbitrary fields
     * @var array
     */
    protected $filterable = ["id"];

    /**
     * List of relation attributes found during parsing of request, to be used during saving action
     * @var array
     */
    protected $relationAttributes = [];

    protected $guarded = [];

    /**
     * Raw attributes as sent in request. To be used in setters of various attributes
     * @var array
     */
    protected $raw = [];

    //region Metadata functions

    /**
     * Name of table of this model
     *
     * @return string
     */
    public static function getTableName()
    {
        return (new static)->table;
    }

    /**
     * Date fields in this model
     *
     * @return array
     */
    public static function getDateFields()
    {
        return (new static)->dates;
    }

    /**
     * List of custom fields (attributes) that are appended by default
     * ($appends array)
     *
     * @return array
     */
    public static function getAppendFields()
    {
        return (new static)->appends;
    }

    /**
     * List of fields to display by default ($defaults array)
     *
     * @return array
     */
    public static function getDefaultFields()
    {
        return (new static)->default;
    }

    /**
     * Return the $relationKeys array
     *
     * @return mixed
     */
    public static function getRelationKeyFields()
    {
        return (new static)->relationKeys;
    }

    /**
     * Returns list of fields on which filter is allowed to be applied
     *
     * @return array
     */
    public static function getFilterableFields()
    {
        return (new static)->filterable;
    }

    /**
     * Checks if given relation exists on the model
     *
     * @param $relation
     * @return bool
     */
    public static function relationExists($relation)
    {
        return method_exists(new static(), $relation);
    }

    //endregion

    /**
     * Prepare a date for array / JSON serialization. Override base method in Model to suite our needs
     *
     * @param  \DateTime  $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format("c");
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * @param  mixed  $value
     * @return \Carbon\Carbon
     */
    protected function asDateTime($value)
    {
        // If this value is already a Carbon instance, we shall just return it as is.
        // This prevents us having to re-instantiate a Carbon instance when we know
        // it already is one, which wouldn't be fulfilled by the DateTime check.
        if ($value instanceof Carbon) {
            return $value;
        }

        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($value instanceof DateTimeInterface) {
            return new Carbon(
                $value->format('Y-m-d H:i:s.u'), $value->getTimeZone()
            );
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp($value);
        }

        // If the value is in simply year, month, day format, we will instantiate the
        // Carbon instances from that format. Again, this provides for simple date
        // fields on the database, while still supporting Carbonized conversion.
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        // Parse ISO 8061 date
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})\\+(\d{2}):(\d{2})$/', $value)) {
            return Carbon::createFromFormat('Y-m-d\TH:i:s+P', $value);
        }
        elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2}T(\d{2}):(\d{2}):(\d{2})\\.(\d{1,3})Z)$/', $value)) {
            return Carbon::createFromFormat('Y-m-d\TH:i:s.uZ', $value);
        }

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        return Carbon::createFromFormat($this->getDateFormat(), $value);
    }

    /**
     * Eagerly load the relationship on a set of models.
     *
     * @param  array  $models
     * @param  string  $name
     * @param  \Closure  $constraints
     * @return array
     */
    protected function loadRelation(array $models, $name, Closure $constraints)
    {
        // First we will "back up" the existing where conditions on the query so we can
        // add our eager constraints. Then we will merge the wheres that were on the
        // query back to it in order that any where conditions might be specified.
        $relation = $this->getRelation($name);

        $relation->addEagerConstraints($models);

        call_user_func($constraints, $relation);

        $models = $relation->initRelation($models, $name);

        // Once we have the results, we just match those back up to their parent models
        // using the relationship instance. Then we just return the finished arrays
        // of models which have been eagerly hydrated and are readied for return.
        $results = $relation->getEager();

        return $relation->match($models, $results, $name);
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param array $attributes
     * @param bool $relations If the attributes also contain relations
     * @return Model
     */
    public function fill(array $attributes = [])
    {
        $this->raw = $attributes;

        $excludes = config("api.excludes");

        foreach ($attributes as $key => $attribute) {
            // Guarded attributes should be removed
            if (in_array($key, $excludes)) {
                unset($attributes[$key]);
            }
            else if (method_exists($this, $key) && ((is_array($attribute) || is_null($attribute)))) {
                // Its a relation
                $this->relationAttributes[$key] = $attribute;

                // For belongs to relation, while filling, we need to set relation key.
                $relation = call_user_func([$this, $key]);

                if ($relation instanceof BelongsTo) {
                    $primaryKey = $relation->getRelated()->getKeyName();

                    if ($attribute !== null) {
                        // If key value is not set in request, we create new object
                        if (!isset($attribute[$primaryKey])) {
                            throw new RelatedResourceNotFoundException('Resource for relation "' . $key . '" not found');
                        }
                        else {
                            $model = $relation->getRelated()->find($attribute[$primaryKey]);

                            if (!$model) {
                                // Resource not found
                                throw new ResourceNotFoundException();
                            }
                        }
                    }

                    $relationKey = $relation->getForeignKeyName();

                    $this->setAttribute($relationKey, ($attribute === null) ? null : $model->getKey());
                }

                unset($attributes[$key]);
            }
        }

        return parent::fill($attributes);
    }

    public function save(array $options = [])
    {
        // Belongs to relation needs to be set before, because we need the parent's Id
        foreach ($this->relationAttributes as $key => $relationAttribute) {
            /** @var Relation $relation */
            $relation = call_user_func([$this, $key]);

            if ($relation instanceof BelongsTo) {
                $primaryKey = $relation->getRelated()->getKeyName();

                if ($relationAttribute !== null) {
                    // If key value is not set in request, we create new object
                    if (!isset($relationAttribute[$primaryKey])) {
                        throw new RelatedResourceNotFoundException('Resource for relation "' . $key . '" not found');
                    }
                    else {
                        $model = $relation->getRelated()->find($relationAttribute[$primaryKey]);

                        if (!$model) {
                            // Resource not found
                            throw new RelatedResourceNotFoundException('Resource for relation "' . $key . '" not found');
                        }
                    }
                }

                $relationKey = $relation->getForeignKeyName();

                $this->setAttribute($relationKey, ($relationAttribute === null) ? null : $model->getKey());

                unset($this->relationAttributes[$key]);
            }
        }

        parent::save($options);

        // Fill all other relations
        foreach ($this->relationAttributes as $key => $relationAttribute) {
            /** @var Relation $relation */
            $relation = call_user_func([$this, $key]);
            $primaryKey = $relation->getRelated()->getKeyName();

            if ($relation instanceof HasOne || $relation instanceof HasMany) {

                if ($relation instanceof HasOne) {
                    $relationAttribute = [$relationAttribute];
                }

                $relationKey = explode(".", $relation->getQualifiedParentKeyName())[1];

                foreach ($relationAttribute as $val) {
                    if ($val !== null) {
                        if (!isset($val[$primaryKey])) {
                            throw new RelatedResourceNotFoundException('Resource for relation "' . $key . '" not found');
                        }
                        else {
                            /** @var Model $model */
                            $model = $relation->getRelated()->find($val[$primaryKey]);

                            if (!$model) {
                                // Resource not found
                                throw new RelatedResourceNotFoundException('Resource for relation "' . $key . '" not found');
                            }

                            // Only update relation key to attach $model to $this object
                            $model->{$relationKey} = $this->getKey();
                            $model->save();
                        }
                    }
                }
            }

            else if ($relation instanceof BelongsToMany) {
                $relatedIds = [];

                // Value is an array of related models
                foreach ($relationAttribute as $val) {
                    if ($val !== null) {
                        if (!isset($val[$primaryKey])) {
                            throw new RelatedResourceNotFoundException('Resource for relation "' . $key . '" not found');
                        }
                        else {
                            /** @var Model $model */
                            $model = $relation->getRelated()->find($val[$primaryKey]);

                            if (!$model) {
                                // Resource not found
                                throw new RelatedResourceNotFoundException('Resource for relation "' . $key . '" not found');
                            }
                        }
                    }

                    if ($val !== null) {
                       if(isset($val['pivot'])) {
                            // We have additional fields other than primary key
                            // that need to be saved to pivot table
                            /*
                                [
                                    {
                                        "id": 12, // Primary key
                                        "pivot": {
                                            "count": 8 // Pivot table column
                                        }
                                    }
                                ]
                             */
                            $relatedIds[$model->getKey()] = $val['pivot'];
                       }
                       else {
                            // We just have ids
                            $relatedIds[] = $model->getKey();
                       }
                   }
                }

                $relation->sync($relatedIds);
            }
        }
    }
}
