<?php

namespace Froiden\RestAPI\Tests;

use Froiden\RestAPI\Tests\Controllers\CommentController;
use Froiden\RestAPI\Tests\Controllers\DummyController;
use Froiden\RestAPI\Tests\Controllers\PostController;
use Froiden\RestAPI\Tests\Controllers\UserController;
use Froiden\RestAPI\Tests\Models\Dummy;
use Froiden\RestAPI\Tests\Models\DummyComment;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class TestCase
 * @package Froiden\RestAPI\Tests
 */
class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     *
     */
    public function setUp()
    {
        parent::setUp();

        $this->app['router']->resource('/dummyUser',  UserController::class);
        $this->app['router']->resource('/dummyPost',  PostController::class);
        $this->app['router']->resource('/dummyComment',  CommentController::class);

        $this->createTables();
        $this->seedDummyData();

    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }


    /**
     * This is the description for the function below.
     *
     * Insert dummy data into tables
     *
     * @return void
     */
    public function seedDummyData()
    {
        $factory = \Illuminate\Database\Eloquent\Factory::construct(\Faker\Factory::create(),
            base_path() . '/laravel-rest-api/tests/Factories');
        \DB::beginTransaction();
        for($i=0; $i<10; $i++)
        {
            $users[]=$factory->of(DummyComment::class)->create();
        }
        \DB::commit();

    }

    /**
     * This is the description for the function below.
     *
     * Create a tables
     *
     * @return void
     */
    public function createTables()
    {
        Schema::dropIfExists('dummyComments');
        Schema::dropIfExists('dummyPosts');
        Schema::dropIfExists('dummyUsers');
        Schema::dropIfExists('dummyPhones');

        Schema::create('dummyPhones', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('modal_no');
            $table->timestamps();
        });

        Schema::create('dummyUsers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->integer('age');
            $table->unsignedInteger('phone_id');
            $table->foreign('phone_id')->references('id')->on('dummyPhones')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->timestamps();
        });

        Schema::create('dummyPosts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('post');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('dummyUsers')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->timestamps();
        });

        Schema::create('dummyComments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('comment');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('dummyUsers')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->unsignedInteger('post_id');
            $table->foreign('post_id')->references('id')->on('dummyPosts')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->timestamps();
        });


    }
}

