@if($counts['user'] > 0)
  <div class="card mb-2">
    <div class="card-header p-1">
      <h5 class="m-1">
        @lang('TurkSim::tours.tptitle')
        <i class="fas fa-spinner float-end"></i>
      </h5>
    </div>
    <div class="card-body text-center p-2">
      @foreach($prog as $tp)
        <div class="progress mb-1" style="height: 20px;">
          <div class="progress-bar {{ $tp['barc'].' '.$tp['warn'] }}" title="{{ $tp['name'].' '.$tp['remd'] }}" role="progressbar" style="width: {{ $tp['prog'] }}%;"
            aria-valuenow="{{ $tp['prog'] }}" aria-valuemin="0" aria-valuemax="100">
            <a href="{{ route('TurkSim.tour').'?code='.$tp['code'] }}" style="color: black;">{{ $tp['name'].' '.$tp['comp'].'/'.$tp['legs'] }}</a>
          </div>
        </div>
      @endforeach
    </div>
    <div class="card-footer p-0 px-1 small fw-bold text-center">
      <span class="float-start">@lang('TurkSim::tours.tpyour') {{ $counts['user'] }}</span>
      <span class="float-end">@lang('TurkSim::tours.tpactive') {{ $counts['tour'] }}</span>
    </div>
  </div>
@endif
