<?php

namespace Froiden\RestAPI\Tests;

use Froiden\RestAPI\Facades\ApiRoute;
use Froiden\RestAPI\Routing\ApiRouter;
use Froiden\RestAPI\Tests\Controllers\CommentController;
use Froiden\RestAPI\Tests\Controllers\PostController;
use Froiden\RestAPI\Tests\Controllers\UserController;
use Froiden\RestAPI\Tests\Models\DummyComment;
use Froiden\RestAPI\Tests\Models\DummyPhone;
use Froiden\RestAPI\Tests\Models\DummyPost;
use Froiden\RestAPI\Tests\Models\DummyUser;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class TestCase
 * @package Froiden\RestAPI\Tests
 */
class  TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost/api/v1';

    /**
     *
     */

    public function setUp()
    {
        parent::setUp();
        
        \DB::statement("SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");

        $this->createTables();
        $this->seedDummyData();

        $this->app[ApiRouter::class]->resource('/dummyUser', UserController::class);
        $this->app[ApiRouter::class]->resource('/dummyPost', PostController::class);
        $this->app[ApiRouter::class]->resource('/dummyComment', CommentController::class);
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

        for($i = 0; $i < 10; $i++)
        {
            $user = $factory->of(DummyUser::class)->create();
            $factory->of(DummyPhone::class)->create(
                [
                    'user_id' => $user->id
                ]
            );

            $post = $factory->of(DummyPost::class)->create(
                [
                    'user_id' => $user->id,
                ]
            );

            $factory->of(DummyComment::class)->create(
                [
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                ]
            );

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
        Schema::dropIfExists('dummy_comments');
        Schema::dropIfExists('dummy_posts');
        Schema::dropIfExists('dummy_phones');
        Schema::dropIfExists('dummy_users');

        Schema::create('dummy_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email', 100)->unique();
            $table->integer('age');
            $table->timestamps();
        });

        Schema::create('dummy_phones', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('modal_no');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('dummy_users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->timestamps();
        });

        Schema::create('dummy_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('post');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('dummy_users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->timestamps();
        });

        Schema::create('dummy_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('comment');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('dummy_users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->unsignedInteger('post_id');
            $table->foreign('post_id')->references('id')->on('dummy_posts')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->timestamps();
        });
    }

}

