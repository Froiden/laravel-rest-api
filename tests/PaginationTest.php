
<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Froiden\RestAPI\Tests\TestCase;

class PaginationTest extends TestCase
{

    /**
     * Test User Index Page.
     *
     * @return void
     **/
    public function testPagination()
    {
        //Use "Limit" to get required number of result
        $response = $this->call('GET', '/dummyUser',
            [
                'order' => 'id asc',
                'offset' => '5',
                'limit' => '2'
            ]);

        dd($response->getContent());
        $this->assertEquals(200, $response->status());
    }
}