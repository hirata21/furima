<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ProfileRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * プロフィール編集画面表示
     */
    public function create()
    {
        $user = auth()->user();
        $address = $user->address;

        return view('mypage.profile_setup', compact('user', 'address'));
    }

    /**
     * プロフィール更新
     */
    public function store(ProfileRequest $request)
    {
        $user = Auth::user();

        DB::transaction(function () use ($request, $user) {
            // 画像アップロードがあれば保存
            if ($request->hasFile('profile_image')) {
                // 既存画像があれば削除（public ディスク）
                if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                    Storage::disk('public')->delete($user->profile_image);
                }
                // 保存先: storage/app/public/profiles
                $path = $request->file('profile_image')->store('profiles', 'public');
                $user->profile_image = $path; // 例: profiles/xxxx.jpg
            }

            // ユーザー本体の更新（ここでは名前など、usersテーブル側だけ）
            $user->name = $request->input('name');
            $user->save();

            // 住所は user_addresses テーブルへ保存（なければ作成、あれば更新）
            // ※ フォームの name が日本語のままなら fallback で拾う
            $payload = [
                'postcode' => $request->input('postcode',  $request->input('郵便番号')),
                'address'  => $request->input('address',   $request->input('住所')),
                'building' => $request->input('building',  $request->input('建物名')),
                'phone'    => $request->input('phone',     $request->input('電話番号')),
            ];

            // 必須の postcode/address が null のままにならないように、必要なら ProfileRequest で required に
            UserAddress::updateOrCreate(
                ['user_id' => $user->id],
                $payload
            );
        });

        // 商品一覧に遷移
        return redirect()->route('items.index')->with('success', 'プロフィールを更新しました。');
    }

    /**
     * マイページ（購入/出品商品）表示
     */
    public function show(Request $request)
    {
        $user = auth()->user();
        $tab = $request->query('tab', 'sell'); // デフォルトは出品

        if ($tab === 'buy') {
            // 自分の購入履歴（itemを同時ロード）→ null安全にitemだけ取り出し
            $purchases    = $user->purchases()->with('item')->latest()->get();
            $items        = $purchases->pluck('item')->filter();     // Collection<Item>
            $purchasedIds = $items->pluck('id')->all();              // [1, 5, 9, ...]
        } else {
            // 自分が出品した商品
            $items        = $user->items()->latest()->get();
            $purchasedIds = []; // 売買タブ以外は空でOK
        }

        return view('mypage.index', compact('user', 'items', 'tab', 'purchasedIds'));
    }
}