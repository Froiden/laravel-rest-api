<?php

namespace Froiden\RestAPI\Tests\Models;

use Froiden\RestAPI\ApiModel;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class DummyPhone extends ApiModel
{

    protected $table = 'dummy_phones';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'modal_no','user_id',
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];
}
