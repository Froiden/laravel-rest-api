<?php

use Froiden\RestAPI\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Routing\Router;

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

    public function testOneToOneRelationWithFieldsParameter()
    {

        $response = $this->call('GET', '/dummyUser',
            [
                'fields' => "id,name,email,phone",
            ]);
        $responseContent = json_decode($response->getContent(), true);
        $this->assertNotNull($responseContent["data"]["0"]["phone"]);
        $this->assertEquals(200, $response->status());
    }

    public function testOneToManyRelationWithFieldsParameter()
    {
        // Get Data With Related Post
        $response = $this->call('GET', '/dummyUser',
            [
                'fields' => "id,name,email,posts",
            ]);
        $responseContent = json_decode($response->getContent(), true);
        $this->assertNotEmpty($responseContent["data"]["0"]["posts"]);
        $this->assertEquals(200, $response->status());

        // Get Data With User Comments on Post
        $response = $this->call('GET', '/dummyUser',
            [
                'fields' => "id,name,email,comments",
            ]);
        $responseContent = json_decode($response->getContent(), true);
        $this->assertNotEmpty($responseContent["data"]["0"]["comments"]);
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
               'order' => "id desc",
            ]);

        $this->assertEquals(200, $response->status());

        $response = $this->call('GET', '/dummyUser',
            [
                'order' => "id asc",
            ]);

        $this->assertEquals(200, $response->status());

    }

    public function testUserShowFunction()
    {
        $user = \Froiden\RestAPI\Tests\Models\DummyUser::all()->random();
        $response = $this->call('GET', '/dummyUser/'.$user->id);

        $this->assertEquals(200, $response->status());
    }

    public function testShowCommentsByUserRelationsEndpoint()
    {
        $user = \Froiden\RestAPI\Tests\Models\DummyUser::all()->random();

        $post = \Froiden\RestAPI\Tests\Models\DummyPost::all()->random();

        $createFactory = \Illuminate\Database\Eloquent\Factory::construct(\Faker\Factory::create(),
            base_path() . '/laravel-rest-api/tests/Factories');

        $comment = $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyComment::class)->create([
            'comment' => "Dummy Comments",
            'user_id' => $user->id,
            'post_id' => $post->id
        ]);
        $response = $this->call('GET', '/dummyUser/'.$user->id.'/comments');

        $responseContent = json_decode($response->getContent(), true);

        $this->assertNotEmpty($responseContent["data"]);

        $this->assertEquals(200, $response->status());
    }

    public function testShowPostsByUserRelationsEndpoint()
    {
        //region Insert Dummy Data
        $user = \Froiden\RestAPI\Tests\Models\DummyUser::all()->random();

        $createFactory = \Illuminate\Database\Eloquent\Factory::construct(\Faker\Factory::create(),
            base_path() . '/laravel-rest-api/tests/Factories');

        $createFactory->of(\Froiden\RestAPI\Tests\Models\DummyPost::class)->create([
            'post' => "dummy POst",
            'user_id' => $user->id,
        ]);

        //endregion

        $response = $this->call('GET', '/dummyUser/'.$user->id.'/posts');

        $responseContent = json_decode($response->getContent(), true);

        $this->assertNotEmpty($responseContent["data"]);

        $this->assertEquals(200, $response->status());
    }

    public function testUserStore()
    {
        $response = $this->call('POST', '/dummyUser',
            [
                'name' => "Dummy User",
                'email' => "dummy@test.com",
                'age' => 25
            ]);
        $this->assertEquals(200, $response->status());

    }

    public function testUserUpdate()
    {
        $user = \Froiden\RestAPI\Tests\Models\DummyUser::all()->random();

        $response = $this->call('PUT', '/dummyUser/'.$user->id,
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
        $user = \Froiden\RestAPI\Tests\Models\DummyUser::all()->random();

        $response = $this->call('DELETE', '/dummyUser/'.$user->id);

        $this->assertEquals(200, $response->status());
    }

}
