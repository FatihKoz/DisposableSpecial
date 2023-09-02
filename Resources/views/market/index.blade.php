@extends('app')
@section('title', 'Market')

@section('content')
  @if(!$items->count())
    <div class="alert alert-info p-1 fw-bold">@lang('DSpecial::common.no_items')</div>
  @else
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
                <img class="card-image img-mh150" src="{{ $item->image_url }}" alt="{{ $item->name }}" title="{{ $item->name }}">
              @endif
                {!! $item->description !!}
            </div>
            <div class="card-footer p-1 text-end">
              @if(!in_array($item->id, $myitems))
                {{ Form::open(['route' => 'DSpecial.market.buy']) }}
                {{ Form::hidden('item_id', $item->id) }}
                {{ Form::button(__('DSpecial::common.buy'), ['type' => 'submit', 'class' => 'btn btn-sm py-0 px-2 ms-2 btn-success float-start']) }}
                {{ Form::close() }}
              @endif
              <button type="button" class="btn btn-sm btn-primary py-0 px-2 ms-2 float-start" data-bs-toggle="modal" data-bs-target="#giftModal{{ $item->id}}">@lang('DSpecial::common.gift')</button>
              {{ money($item->price, $units['currency'], true) }}
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
              {{ Form::open(['route' => 'DSpecial.market.buy', 'class="form-group']) }}
              {{ Form::hidden('item_id', $item->id) }}
              {{ Form::hidden('is_gift', true) }}
                <div class="modal-body p-1">
                  <select name="gift_id" class="form-control form-select">
                    <option value="0" selected>Select a pilot to gift {{ $item->name }}</option>
                    @foreach($users as $u)
                      <option value="{{ $u->id }}">{{ $u->ident.' | '.$u->name_private }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="modal-footer p-1">
                  <button type="button" class="btn btn-sm btn-secondary py-0 px-2" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-sm btn-success py-0 px-2" data-bs-dismiss="modal">@lang('DSpecial::common.gift')</button>
                </div>
              {{ Form::close() }}
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  {{ $items->links('pagination.default') }}
  @endsection
