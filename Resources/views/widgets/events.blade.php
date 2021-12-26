@if($is_visible)
  <div class="card mb-2">
    <div class="card-header p-1">
      <h5 class="m-1">
        Events
        <i class="fas fa-calendar float-end"></i>
      </h5>
    </div>
      <div class="card-body text-center p-2">
        @foreach($prog as $ev)
          <div class="progress mb-1" style="height: 18px;">
            <div class="progress-bar {{ $ev['barc'].' '.$ev['warn'] }}" title="{{ $ev['name'].' '.$ev['remd'] }}" role="progressbar" style="width: {{ $ev['prog'] }}%;"
                 aria-valuenow="{{ $ev['prog'] }}" aria-valuemin="0" aria-valuemax="100">
              <a href="{{ route('DSpecial.tour', [$ev['code']]) }}" style="color: black;">{{ $ev['name'].' '.$ev['comp'].'/'.$ev['legs'] }}</a>
            </div>
          </div>
        @endforeach
      </div>
      <div class="card-footer p-0 px-1 small fw-bold text-center">
        <span class="float-start">Events Started: {{ $counts['user'] }}</span>
        <span class="float-end">All Events: {{ $counts['event'] }}</span>
      </div>
    </div>
@endif
