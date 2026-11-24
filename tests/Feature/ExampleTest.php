<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('the health check returns a successful response', function () {
    $response = $this->get('/up');

    $response->assertOk();
});
