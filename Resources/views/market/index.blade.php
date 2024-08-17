@extends('app')
@section('title', 'Market')

@section('content')
  @if(!$items->count())
    <div class="alert alert-info p-1 fw-bold">@lang('DSpecial::common.no_items')</div>
  @else
    <div class="row row-cols-2 mb-2">
      <div class="col text-start">
        @if($categories)
          <a class="btn btn-sm btn-warning py-0 px-2 mx-1" href="{{ route('DSpecial.market') }}">All Items</a>
          @foreach($categories as $key => $name)
            <a class="btn btn-sm btn-warning py-0 px-2 mx-1" href="?cat={{ $key }}">{{ $name }}</a>
          @endforeach
        @endif
      </div>
      <div class="col text-end">
        @if($items->count() > 1)
          @sortablelink('name', null, null, ['class' => 'btn btn-sm btn-secondary py-0 px-2 mx-1'])
          @sortablelink('price', null, null, ['class' => 'btn btn-sm btn-secondary py-0 px-2 mx-1'])
        @endif
        <a class="btn btn-sm btn-secondary py-0 px-2 mx-1" href="{{ route('DSpecial.market.show', [Auth::id()])}}">@lang('DSpecial::common.mymarket')</a>
      </div>
    </div>
    <div class="row row-cols-lg-4 row-cols-xl-6">
      @foreach($items as $item)
        <div class="col-lg">
          <div class="card mb-2">
            <div class="card-header p-1">
              <h5 class="m-1">
                {{ $item->name }}
                <i class="fas fa-shopping-bag float-end"></i>
              </h5>
            </div>
            <div class="card-body p-1 text-center">
              @if(filled($item->image_url))
                <img class="card-image mw-100 mb-1" src="{{ $item->image_url }}" alt="{{ $item->name }}" title="{{ $item->name }}">
              @endif
              {!! $item->description !!}
            </div>
            <div class="card-footer p-1 text-end">
              @if($item->limit == 0 || $item->owners_count < $item->limit)
                @if(!in_array($item->id, $myitems))
                  <form class="form" method="post" action="{{ route('DSpecial.market.buy') }}">
                    @csrf
                    <input type="hidden" name="item_id" value="{{ $item->id }}" />
                    <button class="btn btn-sm btn-success py-0 px-2 ms-2 float-start" type="submit">{{ __('DSpecial::common.buy') }}</button>
                  </form>
                @endif
                <button type="button" class="btn btn-sm btn-primary py-0 px-2 ms-2 float-start" data-bs-toggle="modal" data-bs-target="#giftModal{{ $item->id}}">@lang('DSpecial::common.gift')</button>
                {{ money($item->price, $units['currency'], $seperation) }}
              @else
                <span class="fw-bold small">SOLD OUT</span>
              @endif
            </div>
          </div>
        </div>
        <!-- Gift Modal -->
        <div class="modal fade" id="giftModal{{ $item->id}}" tabindex="-1" aria-labelledby="giftModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header p-1">
                <h5 class="modal-title p-0" id="giftModalLabel">Gift Market Item</h5>
              </div>
              <form class="form-group" method="post" action="{{ route('DSpecial.market.buy') }}">
                @csrf
                <input type="hidden" name="item_id" value="{{ $item->id }}" />
                <input type="hidden" name="is_gift" value="true" />
                <div class="modal-body p-1">
                  <select name="gift_id" class="form-control form-select">
                    <option value="0" selected>Select a pilot to gift {{ $item->name }}</option>
                    @foreach($users as $u)
                      <option value="{{ $u->id }}">{{ $u->ident.' | '.$u->name_private }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="modal-footer p-1">
                  <button type="button" class="btn btn-sm btn-warning py-0 px-2" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-sm btn-success py-0 px-2" data-bs-dismiss="modal">@lang('DSpecial::common.gift')</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
  {{ $items->links('pagination.default') }}
@endsection
