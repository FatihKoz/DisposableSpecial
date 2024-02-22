@extends('app')
@section('title', 'Personal Items')

@section('content')
  @if(!$items->count())
    <div class="alert alert-info p-1 fw-bold">@lang('DSpecial::common.no_items')</div>
  @else
    <div class="row mb-1">
      <div class="col text-end">
        <a class="btn btn-sm btn-secondary py-0 px-2" href="{{ route('DSpecial.market') }}">@lang('DSpecial::common.market')</a>
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
              <span class="text-start">{!! $item->description !!}</span>
            </div>
            <div class="card-footer p-1 text-end">
              {{ money($item->price, $units['currency'], $seperation) }}
            </div>
            @if(filled($item->notes) && Auth::id() == $owner)
              <div class="card-body p-1 text-start">{!! $item->notes !!}</div>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  @endif

  {{ $items->links('pagination.default') }}
@endsection
