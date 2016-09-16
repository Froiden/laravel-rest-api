<?php

namespace Froiden\RestAPI\Tests\Controllers;

use Froiden\RestAPI\ApiController;
use Froiden\RestAPI\Tests\Models\Comment;

class CommentController extends ApiController
{
    protected $model = Comment::class;
}