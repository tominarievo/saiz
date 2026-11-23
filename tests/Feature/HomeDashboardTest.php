<?php

namespace Tests\Feature;

use App\Disaster;
use App\Http\Controllers\HomeController;
use App\Organization;
use App\Report;
use App\Shelter;
use App\SupportCategory1;
use App\SupportCategory2;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class HomeDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $user = new User();
        $user->username = 'admin@example.test';
        $user->email = 'admin@example.test';
        $user->name = '管理者';
        $user->password = Hash::make('AdminPassword_1!');
        $user->is_valid = true;
        $user->is_system_admin = true;
        $user->save();

        return $user;
    }

    private function createDisaster(): Disaster
    {
        $disaster = new Disaster();
        $disaster->name = 'ダッシュボードテスト災害';
        $disaster->status = true;
        $disaster->event_date = '2026-11-01';
        $disaster->save();

        return $disaster;
    }

    private function createOrganization(): Organization
    {
        $organization = new Organization();
        $organization->name = 'テスト支援団体';
        $organization->status = true;
        $organization->save();

        return $organization;
    }

    private function createShelter(string $name, bool $status = true): Shelter
    {
        $shelter = new Shelter();
        $shelter->name = $name;
        $shelter->status = $status;
        $shelter->save();

        return $shelter;
    }

    private function createSupportCategory(string $name): SupportCategory1
    {
        $category = new SupportCategory1();
        $category->name = $name;
        $category->save();

        return $category;
    }

    private function createSubCategory(SupportCategory1 $category, string $name): SupportCategory2
    {
        $subCategory = new SupportCategory2();
        $subCategory->support_category1_id = $category->id;
        $subCategory->name = $name;
        $subCategory->save();

        return $subCategory;
    }

    private function createReport(
        Organization $organization,
        Disaster $disaster,
        Shelter $shelter,
        Carbon $date,
        array $categories
    ): Report {
        $report = new Report();
        $report->organization_id = $organization->id;
        $report->disaster_id = $disaster->id;
        $report->shelter_id = $shelter->id;
        $report->report_date = $date->toDateString();
        $report->save();

        foreach ($categories as [$subCategory, $signal]) {
            $report->supportCategory2s()->attach($subCategory->id, ['signal' => $signal]);
        }

        return $report;
    }

    public function test_home_dashboard_aggregates_signal_counts_and_map_signals(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $disaster = $this->createDisaster();
        $organization = $this->createOrganization();
        $category = $this->createSupportCategory('支援活動');
        $goods = $this->createSubCategory($category, '物資');
        $people = $this->createSubCategory($category, '人員');

        $shelterA = $this->createShelter('支援先A');
        $shelterB = $this->createShelter('支援先B');
        $shelterC = $this->createShelter('支援先C', false);

        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();
        $twoDaysAgo = $today->copy()->subDays(2);

        $this->createReport($organization, $disaster, $shelterA, $today, [
            [$goods, 3],
            [$people, 1],
        ]);
        $this->createReport($organization, $disaster, $shelterA, $yesterday, [
            [$goods, 2],
        ]);
        $this->createReport($organization, $disaster, $shelterB, $twoDaysAgo, [
            [$goods, 2],
        ]);
        $this->createReport($organization, $disaster, $shelterC, $today, [
            [$goods, 3],
        ]);

        (new HomeController())->index(Request::create('/home', 'GET'));

        $shared = View::getShared();
        $element = collect($shared['count_list'])->first(
            fn ($item) => (int) $item->support_category1->id === (int) $category->id
        );

        $this->assertNotNull($element);
        $this->assertSame(2, $element->list[$today->format('Y/m/d').'.3']);
        $this->assertSame(1, $element->list[$today->format('Y/m/d').'.1']);
        $this->assertSame(1, $element->list[$yesterday->format('Y/m/d').'.2']);
        $this->assertSame(1, $element->list[$twoDaysAgo->format('Y/m/d').'.2']);
        $this->assertSame(3, (int) $element->last_report_signal);

        $mapShelters = collect($shared['shelters'])->keyBy('id');

        $this->assertSame(3, (int) $mapShelters[$shelterA->id]->signal_id);
        $this->assertSame('danger', $mapShelters[$shelterA->id]->signal_css);
        $this->assertSame(2, (int) $mapShelters[$shelterB->id]->signal_id);
        $this->assertSame('warning', $mapShelters[$shelterB->id]->signal_css);
        $this->assertFalse($mapShelters->has($shelterC->id));
    }

    public function test_home_dashboard_query_count_does_not_grow_with_shelters(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $disaster = $this->createDisaster();
        $organization = $this->createOrganization();
        $today = Carbon::today();

        $categories = [];
        $subCategories = [];

        for ($i = 1; $i <= 10; $i++) {
            $category = $this->createSupportCategory('支援活動'.$i);
            $categories[] = $category;
            $subCategories[] = $this->createSubCategory($category, '中分類'.$i);
        }

        for ($i = 1; $i <= 20; $i++) {
            $shelter = $this->createShelter('支援先'.$i);

            foreach ($subCategories as $index => $subCategory) {
                $this->createReport($organization, $disaster, $shelter, $today, [
                    [$subCategory, ($index % 3) + 1],
                ]);
            }
        }

        $queries = 0;

        DB::listen(function () use (&$queries) {
            $queries++;
        });

        (new HomeController())->index(Request::create('/home', 'GET'));

        $this->assertLessThan(
            100,
            $queries,
            'ホーム画面のクエリ数が想定より多いです（N+1の可能性）。queries='.$queries
        );
    }
}
