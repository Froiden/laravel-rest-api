<?php

namespace Froiden\RestAPI\Tests\Controllers;

use Froiden\RestAPI\ApiController;
use Froiden\RestAPI\Tests\Models\DummyPost;

class PostController extends ApiController
{
    protected $model = DummyPost::class;
}