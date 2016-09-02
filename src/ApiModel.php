<?php namespace Froiden\RestAPI;

use Carbon\Carbon;
use DateTimeInterface;
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
    protected $hidden = ["created_at", "updated_at", "pivot"];

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
    protected function serializeDate(\DateTime $date)
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
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2}T(\d{2}):(\d{2}):(\d{2})\\+(\d{2}):(\d{2}))$/', $value)) {
            return Carbon::createFromFormat('Y-m-d\TH:i:s+P', $value);
        }

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        return Carbon::createFromFormat($this->getDateFormat(), $value);
    }

}