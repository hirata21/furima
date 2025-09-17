<?php

namespace App\Http\Controllers;

use App\Models\Item;

class LikeController extends Controller
{
    public function toggle(Item $item)
    {
        $user = auth()->user();
        if ($user->likes()->where('item_id', $item->id)->exists()) {
            $user->likes()->detach($item->id);
        } else {
            $user->likes()->attach($item->id);
        }
        return back();
    }
}
