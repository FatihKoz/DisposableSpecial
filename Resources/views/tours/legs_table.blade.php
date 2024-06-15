<table class="table table-sm table-borderless table-striped text-start text-nowrap align-middle mb-0">
  <tr>
    <th>&nbsp;</th>
    <th>#</th>
    <th>@lang('DSpecial::common.orig')</th>
    <th>@lang('DSpecial::common.dest')</th>
    <th class="text-center">@lang('DSpecial::common.notes')</th>
    <th class="text-center">Valid Between</th>
    <th class="text-center">@lang('DSpecial::common.dist')</th>
    <th class="text-center">@lang('DSpecial::common.block_time')</th>
    <th class="text-center">Actions</th>
    <th class="text-center">@lang('common.status')</th>
  </tr>
  @foreach($tour->legs->sortby('route_leg') as $leg)
    <tr>
      <td><a href="{{ route('frontend.flights.show', [$leg->id]) }}"><i class="fas fa-info-circle ms-2"></i></a></td>
      <td>{{ $leg->route_leg }}</td>
      <td>
        <img class="img-h25 me-1" src="{{ public_asset('/image/flags_new/'.strtolower(optional($leg->dpt_airport)->country).'.png') }}" alt="">
        {{ optional($leg->dpt_airport)->full_name ?? $leg->dpt_airport_id }}
      </td>
      <td>
        <img class="img-h25 me-1" src="{{ public_asset('/image/flags_new/'.strtolower(optional($leg->arr_airport)->country).'.png') }}" alt="">
        {{ optional($leg->arr_airport)->full_name ?? $leg->arr_airport_id }}
      </td>
      <td class="text-center">
        @if($leg->start_date && $leg->end_date)
          <i class="fas fa-calendar-day mx-1 text-danger"
            title="Valid Between: {{ $leg->start_date->format('d.M.Y').' - '.$leg->end_date->format('d.M.Y') }}">
          </i>
        @endif
        @if($leg->subfleets_count > 0)
          <i class="fas fa-plane mx-1 text-primary" title="Valid Only With Assigned Subfleets"></i>
        @endif
      </td>
      <td class="text-center">
        @if($leg->start_date && $leg->end_date)
          {{ $leg->start_date->startOfDay()->format('d.M H:i').' UTC | '.$leg->end_date->endofDay()->format('d.M H:i').' UTC'}}
        @else
          {{ $tour->start_date->startOfDay()->format('d.M H:i').' UTC | '.$tour->end_date->endofDay()->format('d.M H:i').' UTC'}}
        @endif
      </td>
      <td class="text-center">@if($leg->distance[$units['distance']] > 0) {{ number_format($leg->distance[$units['distance']]).' '.$units['distance'] }} @endif</td>
      <td class="text-center">@if($leg->flight_time > 0) @minutestotime($leg->flight_time) @endif</td>
      @if($leg_checks[$leg->route_leg] === true)
        <td class="text-center">&nbsp;</td>
        <td class="text-center">
          <i class="fas fa-check-circle text-success" title="@lang('DSpecial::tours.icontrue')"></i>
        </td>
      @else
        <td class="text-center">
          @if((!setting('pilots.only_flights_from_current') || $leg->dpt_airport_id == optional($user)->curr_airport_id) && ($leg_checks[($leg->route_leg - 1)] === true || $leg->route_leg == 1))
            {{-- Bid --}}
            @if((setting('bids.allow_multiple_bids') === true || setting('bids.allow_multiple_bids') === false && count($saved) === 0))
              <button class="btn btn-sm m-0 mx-1 p-0 px-1 save_flight {{ isset($saved[$leg->id]) ? 'btn-danger':'btn-success' }}"
                    x-id="{{ $leg->id }}"
                    x-saved-class="btn-danger"
                    type="button" title="@lang('flights.addremovebid')">
                <i class="fas fa-map-marker"></i>
              </button>
            @endif
            {{-- SimBrief --}}
            @if($simbrief && ($simbrief_bids === false || $simbrief_bids === true && isset($saved[$leg->id])))
              @php
                $aircraft_id = isset($saved[$leg->id]) ? $user->bids->firstWhere('flight_id', $leg->id)->aircraft_id : null;
              @endphp
              <a href="{{ route('frontend.simbrief.generate') }}?flight_id={{ $leg->id }}@if($aircraft_id)&aircraft_id={{ $aircraft_id }} @endif" class="btn btn-sm m-0 mx-1 p-0 px-1 {{ isset($saved[$leg->id]) ? 'btn-success':'btn-primary' }}">
                <i class="fas fa-file-pdf" title="Generate SimBrief OFP"></i>
              </a>
            @endif
          @endif
        </td>
        <td class="text-center">
          <i class="fas fa-times-circle text-danger" title="@lang('DSpecial::tours.iconfalse')"></i>
        </td>
      @endif
    </tr>
  @endforeach
</table>
@if(setting('bids.block_aircraft', false))
  @include('flights.bids_aircraft')
@endif
@include('flights.scripts')