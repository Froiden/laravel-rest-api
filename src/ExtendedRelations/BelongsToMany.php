<?php
/**
 * Created by PhpStorm.
 * User: Raj Kumar
 * Date: 2/12/19
 * Time: 5:08 PM
 */

namespace Froiden\RestAPI\ExtendedRelations;


use Illuminate\Database\Eloquent\Relations\BelongsToMany as LaravelBelongsToMany;

class BelongsToMany extends LaravelBelongsToMany
{
    /**
     * @return string
     */
    public function getRelatedKeyName() {
        return $this->relatedKey ?: 'id';
    }
}
