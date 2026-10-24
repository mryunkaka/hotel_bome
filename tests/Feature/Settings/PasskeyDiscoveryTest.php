<?php

namespace Tests\Feature\Settings;

use Tests\TestCase;

class PasskeyDiscoveryTest extends TestCase
{
    public function test_public_passkey_discovery_exposes_only_security_endpoint_urls(): void
    {
        $this->get('/.well-known/passkey-endpoints')
            ->assertOk()
            ->assertExactJson([
                'enroll' => route('security.edit'),
                'manage' => route('security.edit'),
            ]);
    }
}
