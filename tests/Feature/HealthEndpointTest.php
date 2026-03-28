<?php

it('returns health status from /api/health', function () {
    $response = $this->getJson('/api/health');

    $response->assertOk()
        ->assertJsonStructure(['agent', 'status', 'version', 'timestamp'])
        ->assertJson([
            'agent' => 'criterion',
            'status' => 'operational',
        ]);
});
