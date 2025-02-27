@if($is_visible)
  <div class="card mb-2">
    <div class="card-header p-1">
      <h5 class="m-1">
        Missions
        <i class="fas fa-hourglass-half float-end"></i>
      </h5>
    </div>
    <div class="card-body text-center p-0 table-responsive">
      <table class="table table-sm table-striped table-borderless mb-0 align-middle text-start text-nowrap">
        <tr>
          <th>Aircraft</th>
          <th>Flight #</th>
          <th>Orig</th>
          <th>Dest</th>
          <th class="text-end">Valid Until</th>
        </tr>
        @foreach($missions as $my)
          <tr>
            <td>
              @if($DBasic)
                <a href="{{ route('DBasic.aircraft', optional($my->aircraft)->registration ?? '-') }}"">{{ optional($my->aircraft)->ident ?? '-' }}</a>
              @else
                {{ optional($my->aircraft)->ident ?? '-' }}
              @endif
            </td>
            <td>
              @if($my->flight)
                <a href="{{ route('frontend.flights.show', [$my->flight->id]) }}">
                  {{ optional($my->flight->airline)->code.' '.$my->flight->flight_number }}
                </a>
              @else
                {{ '-' }}
              @endif
            </td>
            <td>
              <a href="{{ route('frontend.airports.show', [$my->dpt_airport_id]) }}">{{ $my->dpt_airport_id }}</a>
            </td>
            <td>
              <a href="{{ route('frontend.airports.show', [$my->arr_airport_id]) }}">{{ $my->arr_airport_id }}</a>
            </td>
            <th class="text-end">
              {{ $my->mission_valid->diffForHumans() }}
            </th>
          </tr>
        @endforeach
      </table>
    </div>
    <div class="card-footer text-end p-0 px-1 small fw-bold">
        <a href="{{ route('DSpecial.missions') }}">Check All Missions</a>
    </div>
  </div>
@endif
