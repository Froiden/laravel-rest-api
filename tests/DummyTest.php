<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Froiden\RestAPI\Tests\TestCase;

/**
 * Class DummyTest
 */
class DummyTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function testBasicExample()
    {
        $this->assertTrue(true);
        $this->get('dummy')
            ->dontSee('Whoops')
            ->dontSee('Sorry')
            ->see('success');
    }




}

