<?php

namespace Tests\Feature;

use App\Support\SourceLicense;
use Tests\TestCase;

class SourceLicenseTest extends TestCase
{
    public function test_known_sources_map_to_their_license(): void
    {
        $this->assertStringContainsString('CC BY 4.0', SourceLicense::forUrl('https://nlftp.mlit.go.jp/ksj/data.zip')['name']);
        $this->assertStringContainsString('国土地理院', SourceLicense::forUrl('https://hinanmap.gsi.go.jp/csv/13101_1.csv')['name']);
        $this->assertSame('CC BY-SA 4.0', SourceLicense::forUrl('https://geoshape.ex.nii.ac.jp/city/13_city.html')['name']);
        $this->assertStringContainsString('JVOAD', SourceLicense::forUrl('https://jvoad.jp/news')['name']);
        $this->assertStringContainsString('KVOAD', SourceLicense::forUrl('https://www.kvoad.com/news')['name']);
        $this->assertStringContainsString('CVOAD', SourceLicense::forUrl('https://cvoad.jp/news')['name']);
    }

    public function test_government_and_unknown_sources_fall_back(): void
    {
        $this->assertStringContainsString('政府標準利用規約', SourceLicense::forUrl('https://www.pref.chiba.lg.jp/road.pdf')['name']);
        $this->assertSame('出典元の利用規約を確認', SourceLicense::forUrl('https://example.com/road.pdf')['name']);
    }
}
