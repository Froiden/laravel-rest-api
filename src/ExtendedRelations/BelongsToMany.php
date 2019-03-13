<?php

namespace Froiden\RestAPI\ExtendedRelations;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as LaravelBelongsToMany;

class BelongsToMany extends LaravelBelongsToMany
{
    public function getRelatedKeyName() {
        return $this->relatedKey ?: 'id';
    }
}
