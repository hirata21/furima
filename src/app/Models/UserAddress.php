<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserAddress extends Model
{
    use HasFactory;

    // 明示しておくと安心（省略可：UserAddress → user_addresses は自動で推測されます）
    protected $table = 'user_addresses';

    protected $fillable = [
        'user_id',
        'postcode',
        'address',
        'building',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    /** 親ユーザー */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** 便利: 郵便番号 + 住所 + 建物 を連結（表示用） */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([$this->postcode, $this->address, $this->building]);
        return implode(' ', $parts);
    }

    /** ユーザーIDで絞り込み */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
