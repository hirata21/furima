<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Item;
use App\Models\Category;

class ProfileAndListingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function プロフィール編集_初期値が入っている()
    {
        $u = User::factory()->create(['name' => '花子']);
        UserAddress::factory()->create([
            'user_id'  => $u->id,
            'postcode' => '111-2222',
            'address'  => '大阪市',
            'building' => '大阪マンション',
        ]);

        $this->actingAs($u);

        $res = $this->get(route('profile.create'));
        $res->assertOk();
        $res->assertSee('花子');
        $res->assertSee('111-2222');
        $res->assertSee('大阪市');
        $res->assertSee('大阪マンション');
    }

    /** @test */
    public function 出品登録_必要情報が保存される_カテゴリと状態()
    {
        Storage::fake('public');

        $u = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($u);

        $c1 = Category::factory()->create();
        $c2 = Category::factory()->create();

        // GD不要：1x1 PNGの実バイトを使う
        $png1x1 = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg=='
        );
        $file = UploadedFile::fake()->createWithContent('item.png', $png1x1);

        $payload = [
            'name'         => 'テスト商品',
            'brand'        => 'BRAND',
            'description'  => '説明文',
            'price'        => 1200,
            'condition'    => '良好',  // 実装の選択肢に合わせる
            'category_ids' => [$c1->id, $c2->id],
            'image'        => $file,
        ];

        $res = $this->post(route('items.store'), $payload);
        $res->assertRedirect(route('items.index'));
        $res->assertSessionHasNoErrors();

        $this->assertDatabaseHas('items', [
            'name'      => 'テスト商品',
            'brand'     => 'BRAND',
            'condition' => '良好',
            'user_id'   => $u->id,
        ]);

        $this->assertDatabaseCount('category_item', 2);

        $item = Item::where('name', 'テスト商品')->first();
        $this->assertNotNull($item);
        Storage::disk('public')->assertExists($item->image_path);
    }
}