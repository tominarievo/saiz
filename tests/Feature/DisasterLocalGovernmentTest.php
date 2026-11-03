<?php

namespace Tests\Feature;

use App\Disaster;
use App\LocalGovernment;
use App\Prefecture;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DisasterLocalGovernmentTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $admin = new User();
        $admin->username = 'disaster-local-admin@example.test';
        $admin->email = 'disaster-local-admin@example.test';
        $admin->name = '災害管理者';
        $admin->password = Hash::make('AdminPassword_1!');
        $admin->is_valid = true;
        $admin->is_system_admin = true;
        $admin->save();

        return $admin;
    }

    private function createPrefecture(string $code, string $name, int $order): Prefecture
    {
        $prefecture = new Prefecture();
        $prefecture->prefecture_code = $code;
        $prefecture->name = $name;
        $prefecture->order = $order;
        $prefecture->save();

        return $prefecture;
    }

    private function createLocalGovernment(
        Prefecture $prefecture,
        string $code,
        string $name,
        int $order
    ): LocalGovernment {
        $localGovernment = new LocalGovernment();
        $localGovernment->order = $order;
        $localGovernment->prefecture_id = $prefecture->id;
        $localGovernment->prefecture_code = $prefecture->prefecture_code;
        $localGovernment->code = $code;
        $localGovernment->name = $name;
        $localGovernment->government_type = LocalGovernment::GOVERNMENT_TYPE_TOWN;
        $localGovernment->is_designated_city = false;
        $localGovernment->save();

        return $localGovernment;
    }

    private function validPayload(array $localGovernmentIds): array
    {
        return [
            'name' => '市町村登録テスト災害',
            'npo_col_2' => '2026',
            'event_date' => '2026-11-01',
            'local_government_ids' => $localGovernmentIds,
            'disaster_type_ids' => [],
        ];
    }

    public function test_multiple_local_governments_can_be_registered(): void
    {
        $admin = $this->createAdmin();
        $tokyo = $this->createPrefecture('13', '東京都', 13);
        $chiyoda = $this->createLocalGovernment($tokyo, '13101', '千代田区', 1);
        $shinjuku = $this->createLocalGovernment($tokyo, '13104', '新宿区', 2);

        $this->actingAs($admin)
            ->post(route('disasters.store'), $this->validPayload(
                [$chiyoda->id, $shinjuku->id]
            ))
            ->assertRedirect(route('disasters.index'));

        $disaster = Disaster::where('name', '市町村登録テスト災害')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$chiyoda->id, $shinjuku->id],
            $disaster->localGovernments()->pluck('local_governments.id')->all()
        );
    }

    public function test_local_governments_can_be_replaced_on_update(): void
    {
        $admin = $this->createAdmin();
        $tokyo = $this->createPrefecture('13', '東京都', 13);
        $chiyoda = $this->createLocalGovernment($tokyo, '13101', '千代田区', 1);
        $shinjuku = $this->createLocalGovernment($tokyo, '13104', '新宿区', 2);

        $disaster = new Disaster();
        $disaster->name = '更新前の災害';
        $disaster->status = true;
        $disaster->npo_col_2 = '2026';
        $disaster->event_date = '2026-11-01';
        $disaster->save();
        $disaster->localGovernments()->sync([$chiyoda->id, $shinjuku->id]);

        $this->actingAs($admin)
            ->put(route('disasters.update', $disaster->id), $this->validPayload(
                [$shinjuku->id]
            ))
            ->assertRedirect(route('disasters.index'));

        $this->assertSame(
            [$shinjuku->id],
            $disaster->localGovernments()->pluck('local_governments.id')->all()
        );
    }

    public function test_invalid_local_government_is_rejected(): void
    {
        $admin = $this->createAdmin();
        $tokyo = $this->createPrefecture('13', '東京都', 13);

        $this->actingAs($admin)
            ->from(route('disasters.create'))
            ->post(route('disasters.store'), $this->validPayload([999999]))
            ->assertSessionHasErrors('local_government_ids.0');

        $this->assertSame(0, Disaster::count());
    }
}
