<?php

namespace Froiden\RestAPI\Tests\Controllers;

use Froiden\RestAPI\ApiController;
use Froiden\RestAPI\Tests\Models\Post;

class PostController extends ApiController
{
    protected $model = Post::class;
}