<?php

namespace Froiden\RestAPI\Tests\Controllers;

use Froiden\RestAPI\ApiController;
use Froiden\RestAPI\Tests\Models\User;

class UserController extends ApiController
{
    protected $model = User::class;
}