<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'brand',
        'description',
        'price',
        'condition',
        'image_path',
        'is_sold',
    ];

    /** 出品者 */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** カテゴリ（多対多） */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_item', 'item_id', 'category_id')
            ->withTimestamps();
    }

    /** この商品を「いいね」したユーザー */
    public function likes()
    {
        return $this->belongsToMany(User::class, 'likes')->withTimestamps();
    }

    /** コメント一覧 */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /** 購入レコード */
    public function purchase()
    {
        return $this->hasOne(Purchase::class);
    }
}