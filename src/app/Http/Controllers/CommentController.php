<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Http\Requests\CommentRequest;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function store(CommentRequest $request, Item $item): RedirectResponse
    {
        // ★ バリデ済みデータを取得（['comment' => '...']）
        $data = $request->validated();

        // コメント保存
        $item->comments()->create([
            'user_id' => auth()->id(),
            'content' => $data['comment'], // ← DBはcontentに保存
        ]);

        // コメント欄までスクロールするようにフラッシュデータを付与してリダイレクト
        return redirect()->route('items.show', $item->id)
            ->with('success', 'コメントを投稿しました。')
            ->withFragment('comments');
    }
}
