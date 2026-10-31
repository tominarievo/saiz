<?php

namespace Tests\Feature\Security;

use App\Information;
use Tests\TestCase;

class XssTest extends TestCase
{
    public function test_information_html_is_sanitized()
    {
        $information = new Information();
        $information->content = '<p>Hello</p><script>alert(1)</script><a href="javascript:alert(2)">link</a>';

        $result = $information->getEditedContent();

        $this->assertStringNotContainsString('<script', $result);
        $this->assertStringNotContainsString('javascript:', $result);
        $this->assertStringContainsString('Hello', $result);
    }

    public function test_file_data_is_escaped_for_javascript_output()
    {
        $information = new Information();
        $information->file_data = json_encode([
            [
                'file_path' => 'upload_files/test.png',
                'file_name' => '</script><script>alert(1)</script>',
            ],
        ]);

        $json = json_encode(
            $information->getFileDataArray(),
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        );

        $this->assertStringNotContainsString('</script>', $json);
    }
}
