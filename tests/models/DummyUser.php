<?php

namespace Froiden\RestAPI\Tests\Models;

use Froiden\RestAPI\ApiModel;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class DummyUser extends ApiModel
{

    protected $table = 'dummyUsers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'age', 'phone_id',
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * Get the phone record associated with the user.
     */
    public function phone()
    {
        return $this->hasOne('Froiden\RestAPI\Tests\Models\DummyPhone');
    }

    /**
     * The posts that belong to the user.
     */
    public function roles()
    {
        return $this->belongsToMany('Froiden\RestAPI\Tests\Models\DummyPost');
    }

    /**
     * The comments that belong to the user.
     */
    public function comments()
    {
        return $this->belongsToMany('Froiden\RestAPI\Tests\Models\DummyComment');
    }
}
