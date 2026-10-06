<?php

namespace Tests\Admin\Feature;

use Tests\Admin\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_redirects_to_admin(): void
    {
        $this->get('/')->assertRedirect('/admin');
    }
}
