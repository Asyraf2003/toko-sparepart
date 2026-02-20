<?php

test('guest is redirected to login', function () {
    $this->get('/')->assertRedirect('/login');
});
