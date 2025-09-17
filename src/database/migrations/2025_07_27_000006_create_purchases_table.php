<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasesTable extends Migration
{
    public function up()
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');

            // ← 参照先を明示（user_addresses テーブル）
            $table->foreignId('user_address_id')
                ->nullable()
                ->constrained('user_addresses')
                ->nullOnDelete();
            // ★ ここから「購入時点の配送先のスナップショット」3カラム
            $table->string('postcode', 8);
            $table->string('address', 255);
            $table->string('building', 255)->nullable();
            $table->string('payment_method')->nullable(false); // 例: 'コンビニ支払い', 'カード支払い'
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchases');
    }
}