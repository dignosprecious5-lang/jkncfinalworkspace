<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The application root hands off to the Unclassified Records queue.
     */
    public function test_the_application_root_redirects_to_the_intake_screen(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('records.unclassified'));
    }
}
