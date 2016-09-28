<?php

namespace Froiden\RestAPI\Tests\Controllers;

use Froiden\RestAPI\ApiController;
use Froiden\RestAPI\Tests\Models\DummyUser;

class UserController extends ApiController
{
    protected $model = DummyUser::class;
}