
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
        // Pagination set offset = "5" or limit ="3"
        $response = $this->call('GET', '/dummyUser',
            [
                'order' => 'id asc',
                'offset' => '5',
                'limit' => '2'
            ]);
        $this->assertEquals(200, $response->status());

        // Pagination set offset = "1" or limit ="1"
        $response = $this->call('GET', '/dummyUser',
            [
                'order' => 'id asc',
                'offset' => '1',
                'limit' => '1'
            ]);
        $this->assertEquals(200, $response->status());

        // Pagination set offset = "5" or limit ="3"
        $response = $this->call('GET', '/dummyUser',
            [
                'order' => 'id asc',
                'offset' => '5',
                'limit' => '-2'
            ]);
        $this->assertNotEquals(200, $response->status());
    }
}