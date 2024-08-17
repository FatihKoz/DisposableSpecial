<?php

namespace Modules\DisposableSpecial\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Airline;
use App\Models\User;
use App\Models\Enums\UserState;
use App\Services\FinanceService;
use App\Support\Money;
use Carbon\Carbon;
use Modules\DisposableSpecial\Models\Enums\DS_ItemCategory;
use Modules\DisposableSpecial\Models\DS_Marketitem;
use Modules\DisposableSpecial\Models\DS_Marketowner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\DisposableSpecial\Services\DS_NotificationServices;

class DS_MarketController extends Controller
{
    public function index(Request $request)
    {
        $selection = !empty($request->input('cat')) ? $request->input('cat') : null;

        $users = User::whereIn('state', [UserState::ACTIVE, UserState::ON_LEAVE])->get();

        $allcats = DS_ItemCategory::select(false);
        $lstcats = DS_Marketitem::where('active', 1)->groupby('category')->pluck('category')->toArray();
        $categories = array_intersect_key($allcats, array_flip($lstcats));

        $myitems = DS_Marketowner::where('user_id', Auth::id())->orderBy('marketitem_id')->pluck('marketitem_id')->toArray();

        $items = DS_Marketitem::withCount('owners')->when($selection, function ($query) use ($selection) {
            return $query->where('category', $selection);
        })->where('active', 1)->sortable('name', 'price')->paginate(18);

        return view('DSpecial::market.index', [
            'items'      => $items,
            'categories' => (!empty($categories) && count($categories) > 1) ? $categories : null,
            'myitems'    => $myitems,
            'units'      => DS_GetUnits(),
            'users'      => $users,
            'seperation' => (in_array(setting('units.currency'), ['EUR', 'TRY'])) ? false : true,
        ]);
    }

    public function show($id)
    {
        if (!$id) {
            flash()->error('Provide a user ID to proceed!');
            return back();
        }

        $myitems = DS_Marketowner::where('user_id', $id)->orderBy('marketitem_id')->pluck('marketitem_id')->toArray();
        $items = DS_Marketitem::whereIn('id', $myitems)->sortable('name', 'price')->paginate(18);

        return view('DSpecial::market.show', [
            'items'      => $items,
            'owner'      => isset($id) ? $id : null,
            'units'      => DS_GetUnits(),
            'seperation' => (in_array(setting('units.currency'), ['EUR', 'TRY'])) ? false : true,
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
            $item = DS_Marketitem::with('owners')->where('id', $request->input('itemedit'))->first();

            if (!isset($item)) {
                flash()->error('Market item not found!');
                return redirect(route('DSpecial.market_admin'));
            }
        }

        $airlines = Airline::select('id', 'name', 'icao', 'iata')->orderby('name')->get();
        $items = DS_Marketitem::withCount('owners')->sortable('name', 'price')->get();
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
                'limit'         => $request->item_limit,
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
        $item = DS_Marketitem::withCount('owners')->where('id', $request->item_id)->first();

        if (!$item) {
            flash()->error('Item not found!');
            return back();
        }

        if ($item->limit > 0 && $item->owners_count >= $item->limit) {
            flash()->error('Item can not bought/gifted anymore! Usage limit reached.');
            return back();
        }

        $dealer = Airline::with('journal')->where('id', $item->dealer_id)->first();
        $buyer = User::with('journal')->where('id', Auth::id())->first();
        $gifted = ($request->is_gift) ? User::with('journal')->where('id', $request->gift_id)->first() : null;

        if ($request->is_gift && !$gifted) {
            flash()->error('Target user not selected for gifting the item!');
            return back();
        }

        // Check buyer balance
        $amount = Money::createFromAmount($item->price);
        if ($buyer->journal->balance < $amount) {
            flash()->error('Not enough funds!');
            return back();
        }

        // Prepare columns
        if ($gifted) {
            $columns = ['marketitem_id' => $item->id, 'user_id' => $gifted->id];
            $memo = 'Market gift payment for ' . $item->name;

            // Check and abort if target user already owns the items
            $ownership_check = DS_Marketowner::where($columns)->count();

            if ($ownership_check > 0) {
                flash()->info('User already owns the item!');
                return back();
            }
        } else {
            $columns = ['marketitem_id' => $item->id, 'user_id' => $buyer->id];
            $memo = 'Market payment for ' . $item->name;
        }

        DS_Marketowner::create($columns);
        $this->ProcessTransactions($buyer, $dealer, $amount, $memo);

        // Send Message
        if ($item->notifications) {
            $DiscordSVC = app(DS_NotificationServices::class);
            $DiscordSVC->MarketActionMessage($buyer, $item, $gifted);
        }

        flash()->success('Transaction completed for ' . $item->name . ' | ' . $amount);
        return back();
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
