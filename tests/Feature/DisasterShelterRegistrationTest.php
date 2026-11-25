<?php

namespace Tests\Feature;

use App\Disaster;
use App\Shelter;
use Tests\Feature\Security\SecurityTestCase;

class DisasterShelterRegistrationTest extends SecurityTestCase
{
    private function createDisaster(): Disaster
    {
        $disaster = new Disaster();
        $disaster->name = 'テスト災害';
        $disaster->status = true;
        $disaster->save();

        return $disaster;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createShelterWithAddress(string $name, array $attributes = []): Shelter
    {
        $shelter = new Shelter();
        $shelter->name = $name;
        $shelter->status = true;

        foreach ($attributes as $key => $value) {
            $shelter->{$key} = $value;
        }

        $shelter->save();

        return $shelter;
    }

    public function test_edit_separates_registered_and_unregistered_shelters(): void
    {
        $admin = $this->createUser(null);
        $disaster = $this->createDisaster();
        $registered = $this->createShelterWithAddress('登録済み支援先');
        $unregistered = $this->createShelterWithAddress('未登録支援先');

        $disaster->shelters()->attach($registered->id);

        $response = $this->actingAs($admin)
            ->get(route('disaster_shelters.edit', ['disaster_id' => $disaster->id]));

        $response->assertOk();
        $this->assertSame(
            [$registered->id],
            $response->viewData('registered')->pluck('id')->all()
        );
        $this->assertSame(
            [$unregistered->id],
            $response->viewData('candidates')->pluck('id')->all()
        );
    }

    public function test_edit_filters_candidates_by_name(): void
    {
        $admin = $this->createUser(null);
        $disaster = $this->createDisaster();
        $tokyo = $this->createShelterWithAddress('東京ボランティアセンター', [
            'npo_col_11' => '東京都',
            'npo_col_12' => '千代田区',
            'npo_col_3' => '千代田1-1',
        ]);
        $this->createShelterWithAddress('大阪ボランティアセンター', [
            'npo_col_11' => '大阪府',
            'npo_col_12' => '大阪市',
            'npo_col_3' => '北区2-2',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('disaster_shelters.edit', [
                'disaster_id' => $disaster->id,
                'name' => '東京',
            ]));

        $response->assertOk();
        $this->assertSame(
            [$tokyo->id],
            $response->viewData('candidates')->pluck('id')->all()
        );
    }

    public function test_edit_filters_candidates_by_address(): void
    {
        $admin = $this->createUser(null);
        $disaster = $this->createDisaster();
        $this->createShelterWithAddress('東京ボランティアセンター', [
            'npo_col_11' => '東京都',
            'npo_col_12' => '千代田区',
            'npo_col_3' => '千代田1-1',
        ]);
        $osaka = $this->createShelterWithAddress('大阪ボランティアセンター', [
            'npo_col_11' => '大阪府',
            'npo_col_12' => '大阪市',
            'npo_col_3' => '北区2-2',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('disaster_shelters.edit', [
                'disaster_id' => $disaster->id,
                'address' => '大阪市',
            ]));

        $response->assertOk();
        $this->assertSame(
            [$osaka->id],
            $response->viewData('candidates')->pluck('id')->all()
        );
    }

    public function test_update_syncs_registered_shelters_and_redirects_back(): void
    {
        $admin = $this->createUser(null);
        $disaster = $this->createDisaster();
        $first = $this->createShelterWithAddress('支援先A');
        $second = $this->createShelterWithAddress('支援先B');

        $disaster->shelters()->attach($first->id);

        $response = $this->actingAs($admin)
            ->put(route('disaster_shelters.update', ['disaster_id' => $disaster->id]), [
                'shelters' => [$second->id],
            ]);

        $response->assertRedirect(route('disaster_shelters.edit', ['disaster_id' => $disaster->id]));
        $response->assertSessionHas('status');

        $this->assertSame(
            [$second->id],
            $disaster->shelters()->pluck('shelters.id')->all()
        );
    }

    public function test_update_can_clear_all_registered_shelters(): void
    {
        $admin = $this->createUser(null);
        $disaster = $this->createDisaster();
        $shelter = $this->createShelterWithAddress('支援先A');

        $disaster->shelters()->attach($shelter->id);

        $response = $this->actingAs($admin)
            ->put(route('disaster_shelters.update', ['disaster_id' => $disaster->id]));

        $response->assertRedirect(route('disaster_shelters.edit', ['disaster_id' => $disaster->id]));
        $this->assertCount(0, $disaster->shelters()->get());
    }

    public function test_update_rejects_unknown_shelter(): void
    {
        $admin = $this->createUser(null);
        $disaster = $this->createDisaster();

        $response = $this->actingAs($admin)
            ->put(route('disaster_shelters.update', ['disaster_id' => $disaster->id]), [
                'shelters' => [999999],
            ]);

        $response->assertSessionHasErrors('shelters.0');
        $this->assertCount(0, $disaster->shelters()->get());
    }
}
