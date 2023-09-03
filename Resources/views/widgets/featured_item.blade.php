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
      {{ Form::open(['route' => 'DSpecial.market.buy']) }}
      {{ Form::hidden('item_id', $item->id) }}
      {{ Form::button(__('DSpecial::common.buy'), ['type' => 'submit', 'class' => 'btn btn-sm py-0 px-2 ms-2 btn-success float-start']) }}
      {{ Form::close() }}
      {{ money($item->price, $units['currency'], true) }}
    </div>
  </div>
@endif
