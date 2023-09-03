@if($items)
  <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 justify-content-center">
    @foreach($items as $item)
      <div class="col">
        <div class="card mb-2">
          @if(filled($item->image_url))
            <img class="card-image-top mw-100" src="{{ public_asset($item->image_url) }}" alt="{{ $item->name }}" title="{{ $item->name }}">
          @endif
          <div class="card-footer p-1 small text-end">{{ $item->name }}</div>
        </div>
      </div>
    @endforeach
  </div>
@endif
