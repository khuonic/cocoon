<?php

test('homepage requires authentication', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});
