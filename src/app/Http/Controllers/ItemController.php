<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Item;
use App\Models\Category;
use App\Http\Requests\ExhibitionRequest;

class ItemController extends Controller
{
    /**
     * 商品一覧
     */
    public function index(Request $request)
    {
        $user   = auth()->user();
        $reqTab = $request->query('tab');
        $keyword = $request->query('keyword');

        // 1) 明示選択があるときは最優先
        if (in_array($reqTab, ['recommend', 'mylist'], true)) {
            // 選択をセッションに保持（次回 tab なしアクセス時の既定に使う）
            Session::put('preferred_tab', $reqTab);

            if ($reqTab === 'recommend') {
                // 「おすすめ」は最終URLを "/" に正規化（keyword等は維持）
                $params = $request->except('tab');
                return redirect()->route('items.index', $params, 302);
            }

            // mylist はそのまま描画へ
            $tab = $reqTab;
        } else {
            // 2) tab が無いとき：セッション優先 → それも無ければ既定
            $pref = Session::get('preferred_tab'); // 'recommend' or 'mylist'

            if (in_array($pref, ['recommend', 'mylist'], true)) {
                $tab = $pref;   // 直前の明示選択を尊重
            } else {
                // 既定：認証&メール確認済みなら mylist、その他は recommend
                $verified = $user && method_exists($user, 'hasVerifiedEmail')
                    ? $user->hasVerifiedEmail()
                    : false;
                $tab = $verified ? 'mylist' : 'recommend';
                Session::put('preferred_tab', $tab);
            }
        }

        // ===== ここからクエリ =====
        if ($tab === 'mylist') {
            if ($user) {
                $query = $user->likedItems()->orderByDesc('items.created_at');
            } else {
                $query = Item::query()->whereRaw('0=1');
            }
        } else { // recommend
            $query = Item::query()->latest();
            // 例）未販売のみ：$query->where('is_sold', false);
        }

        if (!empty($keyword)) {
            $query->where('name', 'like', "%{$keyword}%");
        }

        // ★ ログイン中は「自分の出品」を一覧から除外
        if (Auth::check()) {
            $uid = Auth::id();

            // user_id が自分以外、もしくは NULL のものは表示（NULL も見せたい場合の安全策）
            $query->where(function ($q) use ($uid) {
                $q->where('items.user_id', '<>', $uid)
                    ->orWhereNull('items.user_id');
            });
        }

        $items = $query->paginate(24)->withQueryString();

        return view('items.index', compact('items', 'tab'))->with(['keyword' => $keyword]);
    }

    /**
     * 商品詳細
     */
    public function show(Item $item)
    {
        $item->load(['likes', 'comments.user', 'categories']);

        $liked = auth()->check()
            ? $item->likes()->where('user_id', auth()->id())->exists()
            : false;

        return view('items.show', compact('item', 'liked'));
    }

    /**
     * 出品フォーム表示
     */
    public function create()
    {
        $categories = Category::all();
        return view('items.create', compact('categories'));
    }

    /**
     * 出品処理
     */
    public function store(ExhibitionRequest $request)
    {
        $path = $request->hasFile('image')
            ? $request->file('image')->store('items', 'public')
            : null;

        $item = Item::create([
            'user_id'     => auth()->id(),
            'name'        => $request->name,
            'brand'       => $request->brand,
            'description' => $request->description,
            'price'       => $request->price,
            'condition'   => $request->condition,
            'image_path'  => $path,
            'is_sold'     => false,
        ]);

        // 複数カテゴリを中間テーブルに保存
        $item->categories()->attach($request->category_ids);

        return redirect()->route('items.index')->with('success', '商品を出品しました');
    }
}
