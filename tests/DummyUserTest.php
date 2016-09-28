<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Froiden\RestAPI\Tests\TestCase;

class DummyUserTest extends TestCase
{
    /**
     * Test User Index Page.
     *
     * @return void
     */

    public function testUserIndex()
    {
        // Send Simple Index Request
        $response = $this->call('GET', '/dummyUser');

        $this->assertEquals(200, $response->status());
    }

    public function testUserIndexWithFields()
    {
        $response = $this->call('GET', '/dummyUser',
            [
                'fields' => "id,name,email,age",
            ]);
        $this->assertEquals(200, $response->status());
    }

    public function testCallIndexWithRelationsInFields()
    {
        // Get Data With Related Post
        $response = $this->call('GET', '/dummyUser',
            [
                'fields' => "id,name,email,posts",
            ]);
        $this->assertEquals(200, $response->status());

        // Get Data With User Comments on Post
        $response = $this->call('GET', '/dummyUser',
            [
                'fields' => "id,name,email,comments",
            ]);
        $this->assertEquals(200, $response->status());

        // Get Phone related to user
        $response = $this->call('GET', '/dummyUser',
              [
                  'fields' => "id,name,email,phone",
              ]);

        $this->assertEquals(200, $response->status());

    }

    public function testUserIndexWithFilters()
    {
        $createFactory = \Illuminate\Database\Eloquent\Factory::construct(\Faker\Factory::create(),
            base_path() . '/laravel-rest-api/tests/Factories');

        $userId = $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyUser::class)->create();

        // Use "filters" to modify The result
        $response = $this->call('GET', '/dummyUser',
            [
                'filters' => 'age lt 7',
            ]);
        $this->assertEquals(200, $response->status());

        // With 'lk' operator
        $response = $this->call('GET', '/dummyUser',
            [
                'fields' => "id,name",
                'filters' => 'name lk "%'.$userId->name.'%"',
            ]);
        $this->assertEquals(200, $response->status());
    }

    public function testUserIndexWithLimit()
    {
        // Use "Limit" to get required number of result
        $response = $this->call('GET', '/dummyUser',
            [
                'limit' => '5',
            ]);

        $this->assertEquals(200, $response->status());
    }

    public function testUserIndexWithsOrderParameter()
    {
        // Define order of result
        $response = $this->call('GET', '/dummyUser',
            [
               'order' => "id asc",
            ]);
        $this->assertEquals(200, $response->status());

    }

    public function testUserShowFunction()
    {
        //region Insert Dummy Data
        $createFactory = \Illuminate\Database\Eloquent\Factory::construct(\Faker\Factory::create(),
            base_path() . '/laravel-rest-api/tests/Factories');

        $userId = $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyUser::class)->create()->id;

        $post = $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyPost::class)->create([
            'post' => "dummy POst",
            'user_id' => $userId,
        ]);

        $comment = $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyComment::class)->create([
            'comment' => "Dummy Comments",
            'user_id' => $userId,
            'post_id' => $post->id,
        ]);
        //endregion

        $response = $this->call('GET', '/dummyUser/'.$userId);

        $this->assertEquals(200, $response->status());
    }

    public function testShowCommentsByUserRelationsEndpoint()
    {
        //region Insert Dummy Data
        $createFactory = \Illuminate\Database\Eloquent\Factory::construct(\Faker\Factory::create(),
            base_path() . '/laravel-rest-api/tests/Factories');

        $userId = $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyUser::class)->create()->id;

        $post = $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyPost::class)->create([
            'post' => "dummy POst",
            'user_id' => $userId,
        ]);

        $comment = $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyComment::class)->create([
            'comment' => "Dummy Comments",
            'user_id' => $userId,
            'post_id' => $post->id,
        ]);
        //endregion

        $response = $this->call('GET', '/dummyUser/'.$userId.'/comments');

        $this->assertEquals(200, $response->status());
    }

    public function testShowPostsByUserRelationsEndpoint()
    {
        //region Insert Dummy Data
        $createFactory = \Illuminate\Database\Eloquent\Factory::construct(\Faker\Factory::create(),
            base_path() . '/laravel-rest-api/tests/Factories');

        $userId = $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyUser::class)->create()->id;

        $post = $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyPost::class)->create([
            'post' => "dummy POst",
            'user_id' => $userId,
        ]);

        $comment = $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyComment::class)->create([
            'comment' => "Dummy Comments",
            'user_id' => $userId,
            'post_id' => $post->id,
        ]);
        //endregion

        $response = $this->call('GET', '/dummyUser/'.$userId.'/posts');

        $this->assertEquals(200, $response->status());
    }

    public function testUserStoreFunction()
    {
        $response = $this->call('POST', '/dummyUser',
            [
                'name' => "Dummy User",
                'email' => "dummy@test.com",
                'age' => 25
            ]);
        $this->assertEquals(200, $response->status());

    }

    public function testUserUpdateFunction()
    {
        $createFactory = \Illuminate\Database\Eloquent\Factory::construct(\Faker\Factory::create(),
            base_path() . '/laravel-rest-api/tests/Factories');

        $userId = $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyUser::class)->create()->id;

        $response = $this->call('PUT', '/dummyUser/'.$userId,
            [
                'name' => "Dummy1 User",
                'email' => "dummy2@test.com",
                'age' => 25,
            ]);
        $this->assertEquals(200, $response->status());
    }

    /**
     * Test User Delete  Function.
     *
     * @return void
     */
    public function testUserDelete()
    {
        $createFactory = \Illuminate\Database\Eloquent\Factory::construct(\Faker\Factory::create(),
            base_path() . '/laravel-rest-api/tests/Factories');

        $userId = $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyUser::class)->create()->id;

        $response = $this->call('DELETE', '/dummyUser/'.$userId);

        $this->assertEquals(200, $response->status());
    }

}
