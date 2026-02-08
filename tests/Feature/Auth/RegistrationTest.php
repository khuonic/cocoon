<?php

// Registration is intentionally disabled — only whitelisted users can access the app.

it('registration route does not exist', function () {
    $response = $this->get('/register');

    $response->assertNotFound();
});
