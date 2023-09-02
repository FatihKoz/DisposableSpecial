<?php

namespace Modules\DisposableSpecial\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Airline;
use App\Models\User;
use App\Services\FinanceService;
use App\Support\Money;
use Carbon\Carbon;
use Modules\DisposableSpecial\Models\Enums\DS_ItemCategory;
use Modules\DisposableSpecial\Models\DS_Marketitem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DS_MarketController extends Controller
{
    public function index()
    {
        $myitems = DB::table('disposable_marketitem_owner')->where('user_id', Auth::id())->orderBy('marketitem_id')->pluck('marketitem_id')->toArray();
        $items = DS_Marketitem::where('active', 1)->sortable('name', 'price')->paginate(15);
        $users = User::get();

        return view('DSpecial::market.index', [
            'items'   => $items,
            'myitems' => $myitems,
            'units'   => DS_GetUnits(),
            'users'   => $users,
        ]);
    }

    public function owned()
    {
        $myitems = DB::table('disposable_marketitem_owner')->where('user_id', Auth::id())->orderBy('marketitem_id')->pluck('marketitem_id')->toArray();
        $items = DS_Marketitem::whereIn('id', $myitems)->sortable('name', 'price')->paginate(15);

        return view('DSpecial::market.owned', [
            'items' => $items,
        ]);
    }

    public function index_admin(Request $request)
    {
        if ($request->input('itemdelete')) {
            DS_Marketitem::where('id', $request->input('itemdelete'))->delete();
            flash()->warning('Market item deleted!');
            return redirect(route('DSpecial.market_admin'));
        }

        if ($request->input('itemedit')) {
            $item = DS_Marketitem::where('id', $request->input('itemedit'))->first();

            if (!isset($item)) {
                flash()->error('Market item not found!');
                return redirect(route('DSpecial.market_admin'));
            }
        }

        $airlines = Airline::select('id', 'name', 'icao', 'iata')->orderby('name')->get();
        $items = DS_Marketitem::sortable('name', 'price')->get();
        $categories = DS_ItemCategory::select(true);

        return view('DSpecial::admin.market', [
            'airlines'   => isset($airlines) ? $airlines : null,
            'categories' => $categories,
            'item'       => isset($item) ? $item : null,
            'items'      => $items,
        ]);
    }

    // Insert or Update items
    public function store(Request $request)
    {
        if (!$request->item_name || !$request->item_price || !$request->item_dealer) {
            flash()->error('Name, price and dealer fields are mandatory !');
            return back();
        }

        DS_Marketitem::updateOrCreate(
            [
                'id' => $request->item_id,
            ],
            [
                'name'          => $request->item_name,
                'price'         => $request->item_price,
                'description'   => $request->item_description,
                'notes'         => $request->item_notes,
                'image_url'     => $request->item_image_url,
                'category'      => $request->item_category,
                'dealer_id'     => $request->item_dealer,
                'active'        => $request->item_active,
                'notifications' => $request->item_notifications,
            ]
        );

        flash()->success('Market item saved');
        return back();
    }

    // Buy item
    public function buy(Request $request)
    {
        $item = DS_Marketitem::where('id', $request->item_id)->first();

        if (!$item) {
            flash()->error('Item not found!');
            return back();
        }

        $owner = User::with('journal')->where('id', Auth::id())->first();
        $dealer = Airline::with('journal')->where('id', $item->dealer_id)->first();

        // Check owner balance
        $amount = Money::createFromAmount($item->price);
        if ($owner->journal->balance < $amount) {
            flash()->error('Not enough funds!');
            return back();
        }

        // Update ownership and process transacitons
        $columns = ['marketitem_id' => $item->id, 'user_id' => $owner->id];
        $memo = 'Market payment for ' . $item->name;

        DB::table('disposable_marketitem_owner')->updateOrInsert($columns, $columns);
        $this->ProcessTransactions($owner, $dealer, $amount, $memo);

        flash()->success('Successfully bought ' . $item->name . ' for ' . $amount);
        return back();
    }

    // Gift item
    public function gift(Request $request)
    {
        $item = DS_Marketitem::where('id', $request->item_id)->first();

        if (!$item || !$request->gift_id || $request->gift_id == 0) {
            flash()->error('Check item or user selection for gifting!');
            return back();
        }

        $owner = User::with('journal')->where('id', Auth::id())->first();
        $dealer = Airline::with('journal')->where('id', $item->dealer_id)->first();

        // Check owner balance
        $amount = Money::createFromAmount($item->price);
        if ($owner->journal->balance < $amount) {
            flash()->error('Not enough funds!');
            return back();
        }

        // Update ownership and process transacitons
        $columns = ['marketitem_id' => $item->id, 'user_id' => $request->gift_id];
        $memo = 'Market gift payment for ' . $item->name;

        $ownership_check = DB::table('disposable_marketitem_owner')->where($columns)->count();

        if ($ownership_check > 0) {
            flash()->info('User already owns the item!');
            return back();
        } else {
            DB::table('disposable_marketitem_owner')->updateOrInsert($columns, $columns);
            $this->ProcessTransactions($owner, $dealer, $amount, $memo);

            flash()->success('Successfully gifted ' . $item->name . ' for ' . $amount);
            return back();
        }
    }

    // Process Transactions
    public function ProcessTransactions($user, $airline, $amount, $memo)
    {
        $financeSvc = app(FinanceService::class);

        // Charge User (owner)
        $financeSvc->debitFromJournal(
            $user->journal,
            $amount,
            $user,
            $memo,
            'Market Actions',
            'market',
            Carbon::now()->format('Y-m-d')
        );

        // Credit Airline (dealer)
        $financeSvc->creditToJournal(
            $airline->journal,
            $amount,
            $user,
            $memo . ' UserID:' . $user->id,
            'Market Actions',
            'market',
            Carbon::now()->format('Y-m-d')
        );

        // Note Transaction
        Log::debug('Disposable Special | UserID:' . $user->id . ' Name:' . $user->name_private . ' charged for ' . $memo);
    }
}
