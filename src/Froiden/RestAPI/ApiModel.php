<?php namespace Froiden\RestAPI;

use Illuminate\Database\Eloquent\Model;

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
    protected $hidden = ["created_at", "updated_at", "company_id", "pivot"];

    /**
     * List of fields on which filters are allowed to be applied. For security
     * reasons we cannot allow filters to be allowed on arbitrary fields
     * @var array
     */
    protected $filterable = ["id"];

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
//    protected function serializeDate(\DateTime $date) {
//        return $date->format("c");
//    }

}