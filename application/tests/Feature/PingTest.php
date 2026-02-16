<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PingTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_ping_endpoint_respond_with_pong(): void
    {
        $response = $this->get("/api/ping");

        $response->assertStatus(200)->assertJson([
            "msg" => "pong",
            "err" => false,
        ]);
    }
}
