<?php

it('serves the login page', function () {
    $this->get('/login')->assertOk();
});
