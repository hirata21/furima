<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class MyListController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // クエリパラメータでマイリスト表示を指定された場合
        if ($request->query('tab') === 'mylist') {
            $items = $user->likes()->latest()->get();
        } else {
            $items = Item::latest()->get();
        }

        return view('item.index', compact('items'));
    }
}
