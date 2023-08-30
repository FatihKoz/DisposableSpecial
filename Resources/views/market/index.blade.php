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
              {{ Form::open(['route' => 'DSpecial.market.buy']) }}
              {{ Form::hidden('item_id', $item->id) }}
              {{ Form::button(__('DSpecial::common.buy'), ['type' => 'submit', 'class' => 'btn btn-sm py-0 px-2 btn-success float-start']) }}
              {{ Form::close() }}
              {{ $item->price.' '.$units['currency'] }}
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  {{ $items->links('pagination.default') }}
@endsection
