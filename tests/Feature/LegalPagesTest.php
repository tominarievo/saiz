<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_privacy_policy_is_public(): void
    {
        $this->get('/privacy')
            ->assertOk()
            ->assertSee('プライバシーポリシー')
            ->assertSee('外部AI')
            ->assertSee('越境移転')
            ->assertSee('削除')
            ->assertSee('info@evolinq.link');
    }

    public function test_terms_links_to_privacy_policy(): void
    {
        $this->get('/terms')
            ->assertOk()
            ->assertSee('プライバシーポリシー');
    }
}
