@if($item)
  <div class="card mb-2">
    <div class="card-header p-1">
      <h5 class="m-1">
        @lang('DSpecial::common.featured')
        <i class="fas fa-shopping-bag float-end"></i>
      </h5>
    </div>
    <div class="card-body text-center p-1 pb-0">
      <h5 class="card-title">{{ $item->name }}</h5>
      @if(filled($item->image_url))
        <img class="card-image mw-100 mb-1" src="{{ public_asset($item->image_url) }}" alt="{{ $item->name }}" title="{{ $item->name }}">
      @endif
      {!! $item->description !!}
    </div>
    <div class="card-footer p-1 text-end">
      <form class="form" method="post" action="{{ route('DSpecial.market.buy') }}">
        @csrf
        <input type="hidden" name="item_id" value="{{ $item->id }}" />
        <button class="btn btn-sm btn-success py-0 px-2 ms-2 float-start" type="submit">{{ __('DSpecial::common.buy') }}</button>
      </form>
      {{ money($item->price, $units['currency'], $seperation) }}
    </div>
  </div>
@endif
