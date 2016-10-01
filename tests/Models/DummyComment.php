<?php

namespace Froiden\RestAPI\Tests\Models;

use Froiden\RestAPI\ApiModel;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class DummyComment extends ApiModel
{

    protected $table = 'dummy_comments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'comments', 'user_id', 'post_id',
    ];

    protected $filterable = [
        'comments', 'user_id', 'post_id',
    ];


    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * Get the post that owns the comment.
     */
    public function post()
    {
        return $this->belongsTo('Froiden\RestAPI\Tests\Models\DummyPost');
    }
}
