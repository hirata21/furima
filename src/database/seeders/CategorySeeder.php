<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['id' => 1, 'name' => 'ファッション'],
            ['id' => 2, 'name' => '家電'],
            ['id' => 3, 'name' => 'インテリア'],
            ['id' => 4, 'name' => 'レディース'],
            ['id' => 5, 'name' => 'メンズ'],
            ['id' => 6, 'name' => 'コスメ'],
            ['id' => 7, 'name' => '本'],
            ['id' => 8, 'name' => 'ゲーム'],
            ['id' => 9, 'name' => 'スポーツ'],
            ['id' => 10, 'name' => 'キッチン'],
            ['id' => 11, 'name' => 'ハンドメイド'],
            ['id' => 12, 'name' => 'アクセサリー'],
            ['id' => 13, 'name' => 'おもちゃ'],
            ['id' => 14, 'name' => 'ベビー・キッズ'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['id' => $category['id']], // 条件
                ['name' => $category['name']] // 更新内容
            );
        }

        // AUTO_INCREMENT を最大ID+1 に調整（新規追加時の競合防止）
        DB::statement('ALTER TABLE categories AUTO_INCREMENT = 15;');
    }
}
