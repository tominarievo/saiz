<?php

namespace Tests\Feature;

use App\Services\DisasterIntelligence\SourceTextRedactor;
use Tests\TestCase;

class SourceTextRedactorTest extends TestCase
{
    private function redactor(): SourceTextRedactor
    {
        return app(SourceTextRedactor::class);
    }

    public function test_redacts_high_risk_personal_information(): void
    {
        config([
            'disaster-intelligence.redaction.enabled' => true,
            'disaster-intelligence.redaction.strict' => false,
        ]);

        $text = implode("\n", [
            '氏名：山田太郎',
            '連絡先: 090-1234-5678',
            'メール: taro@example.com',
            '個人番号 1234-5678-9012',
            '山田太郎さん',
            '道路: 国道1号 2026-11-08 全面通行止め',
        ]);

        $result = $this->redactor()->redact($text);

        $this->assertStringContainsString('氏名：[REDACTED]', $result);
        $this->assertStringContainsString('連絡先: [REDACTED]', $result);
        $this->assertStringContainsString('[MYNUMBER]', $result);
        $this->assertStringContainsString('[氏名]さん', $result);
        $this->assertStringNotContainsString('山田太郎', $result);
        $this->assertStringNotContainsString('090-1234-5678', $result);
        $this->assertStringNotContainsString('taro@example.com', $result);
        $this->assertStringContainsString('国道1号', $result);
        $this->assertStringContainsString('2026-11-08', $result);
        $this->assertStringContainsString('全面通行止め', $result);
    }

    public function test_redacts_standalone_email_and_phone(): void
    {
        config([
            'disaster-intelligence.redaction.enabled' => true,
            'disaster-intelligence.redaction.strict' => false,
        ]);

        $result = $this->redactor()->redact('連絡は taro@example.com または 090-1234-5678 まで。');

        $this->assertStringContainsString('[EMAIL]', $result);
        $this->assertStringContainsString('[PHONE]', $result);
        $this->assertStringNotContainsString('taro@example.com', $result);
        $this->assertStringNotContainsString('090-1234-5678', $result);
    }

    public function test_safe_mode_keeps_addresses_and_postal_codes(): void
    {
        config([
            'disaster-intelligence.redaction.enabled' => true,
            'disaster-intelligence.redaction.strict' => false,
        ]);

        $text = "住所：東京都千代田区1-1\n〒100-0001";
        $result = $this->redactor()->redact($text);

        $this->assertSame($text, $result);
    }

    public function test_strict_mode_redacts_addresses_and_postal_codes(): void
    {
        config([
            'disaster-intelligence.redaction.enabled' => true,
            'disaster-intelligence.redaction.strict' => true,
        ]);

        $result = $this->redactor()->redact("住所：東京都千代田区1-1\n〒100-0001\n国道1号");

        $this->assertStringContainsString('住所：[ADDRESS]', $result);
        $this->assertStringContainsString('[POSTAL]', $result);
        $this->assertStringNotContainsString('東京都千代田区', $result);
        $this->assertStringContainsString('国道1号', $result);
    }

    public function test_redact_array_applies_to_nested_strings(): void
    {
        config([
            'disaster-intelligence.redaction.enabled' => true,
            'disaster-intelligence.redaction.strict' => false,
            'disaster-intelligence.redaction.ner.enabled' => false,
        ]);

        $result = $this->redactor()->redactArray([
            'road_name' => '国道1号',
            'section' => '連絡先: 090-1234-5678',
            'metadata' => ['email' => 'taro@example.com'],
        ]);

        $this->assertSame('国道1号', $result['road_name']);
        $this->assertStringContainsString('[REDACTED]', $result['section']);
        $this->assertStringContainsString('[EMAIL]', $result['metadata']['email']);
    }

    public function test_ner_is_used_when_enabled(): void
    {
        config([
            'disaster-intelligence.redaction.enabled' => true,
            'disaster-intelligence.redaction.strict' => false,
            'disaster-intelligence.redaction.ner.enabled' => true,
            'disaster-intelligence.redaction.ner.python' => PHP_BINARY,
            'disaster-intelligence.redaction.ner.script' => base_path('tests/fixtures/fake_pii_ner.php'),
            'disaster-intelligence.redaction.ner.timeout' => 10,
        ]);

        $result = $this->redactor()->redact('山田太郎が現地を確認しました。');

        $this->assertStringContainsString('[氏名]', $result);
        $this->assertStringNotContainsString('山田太郎', $result);
        $this->assertStringContainsString('現地を確認しました', $result);
    }

    public function test_redaction_can_be_disabled(): void
    {
        config([
            'disaster-intelligence.redaction.enabled' => false,
            'disaster-intelligence.redaction.strict' => false,
        ]);

        $text = "氏名：山田太郎\nメール: taro@example.com";
        $result = $this->redactor()->redact($text);

        $this->assertSame($text, $result);
    }
}
