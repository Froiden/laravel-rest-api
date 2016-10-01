<?php

namespace Froiden\RestAPI\Tests\Models;

use Froiden\RestAPI\ApiModel;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class DummyPost extends ApiModel
{

    protected $table = 'dummy_posts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'post', 'user_id',
    ];

    protected $filterable = [
        'post', 'user_id',
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * Get the comments for the blog post.
     */
    public function comments()
    {
        return $this->hasMany('Froiden\RestAPI\Tests\Models\DummyComment');
    }

}
