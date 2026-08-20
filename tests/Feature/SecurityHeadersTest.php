<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_public_response_has_x_frame_options_header(): void
    {
        $this->get('/')
            ->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_public_response_has_x_content_type_options_header(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_public_response_has_referrer_policy_header(): void
    {
        $this->get('/')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_public_response_has_permissions_policy_header(): void
    {
        $this->get('/')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
