@extends('app')
@section('title', 'Missions')

@section('content')
  @if($my_missions->count() > 0)
    <div class="row row-cols-2 mb-2">
      {{-- My Missions --}}
      <div class="col-8">
        <div class="card mb-2">
          <div class="card-header p-1 fw-bold">
            <h5 class="m-1">
              My Missions
              <i class="fas fa-user float-end"></i>
            </h5>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table table-sm table-striped table-secondary mb-0">
              <tr>
                <th>#</th>
                <th>Aircraft</th>
                <th>Company</th>
                <th>Flight Number</th>
                <th>Origin</th>
                <th>STD</th>
                <th>STA</th>
                <th>Destination</th>
                <th>Valid Until</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
              </tr>
              @foreach($my_missions as $miss)
                <tr>
                  <td>{{ $miss->mission_order }}</td>
                  <td>{{ $miss->aircraft->ident }}</td>
                  <td>{{ $miss->aircraft->airline->name }}</td>
                  <td>
                    @if($miss->flight)
                      {{ $miss->flight->airline->code.' '.$miss->flight->flight_number }}
                    @else
                      {{ '-' }}
                    @endif
                  </td>
                  <td>{{ $miss->dpt_airport->id }}</td>
                  <td>
                    @if($miss->flight)
                      {{ DS_FormatScheduleTime($miss->flight->dpt_time) }}
                    @endif
                  </td>
                  <td>
                    @if($miss->flight)
                      {{ DS_FormatScheduleTime($miss->flight->arr_time) }}
                    @endif
                  </td>
                  <td>{{ $miss->arr_airport->id }}</td>
                  <td>{{ $miss->mission_valid->format('d.M.y H:i') }}</td>
                  <td class="text-end">
                    @if($miss->flight)
                      <a href="{{ route('frontend.flights.show', [$miss->flight->id]) }}" class="btn btn-sm btn-success py-0 px-1">Flight Details</a>
                    @else
                      <a href="{{ route('DSpecial.freeflight') }}" class="btn btn-sm btn-success py-0 px-1">Free Flight</a>
                    @endif
                  </td>
                  <td class="text-end">
                    <form action={{ route('DSpecial.missions.store') }} method="POST">
                      @csrf
                      <input type="hidden" name="remove_id" value="{{ $miss->id }}">
                      <input type="hidden" name="action" value="remove">
                      <button type="submit" class="btn btn-sm btn-danger py-0 px-1">Remove Mission</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </table>
          </div>
          <div class="card-footer p-1 text-end small">Available Missions: {{ count($my_missions) }}</div>
        </div>
      </div>
    </div>
  @endif

  <div class="row row-cols-2 mb-2">
    {{-- Maintenance Flights --}}
    <div class="col">
      <div class="card mb-2">
        <div class="card-header p-1 fw-bold">
          <h5 class="m-1">
            Mission: Bring Back Maintanence Required Aircraft
            <i class="fas fa-wrench float-end"></i>
          </h5>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-sm table-striped table-secondary mb-0">
            <tr>
              <th>Aircraft</th>
              <th>Company</th>
              <th>Flight Number</th>
              <th>Origin</th>
              <th>Destination</th>
              <th>Valid Until</th>
              <th>&nbsp;</th>
            </tr>
            @foreach($mt_missions as $miss)
              <tr>
                <td>{{ $miss['ac']->ident }}</td>
                <td>{{ $miss['ac']->airline->name }}</td>
                <td>
                  @if(filled($miss['flt']))
                    {{ $miss['flt']->airline->code.' '.$miss['flt']->flight_number }}
                  @else
                    {{ '-' }}
                  @endif
                </td>
                <td>{{ $miss['dep']->id }}</td>
                <td>{{ $miss['arr']->id }}</td>
                <td>{{ $miss['end']->diffForHumans() }}</td>
                <td class="text-end">
                  <form action={{ route('DSpecial.missions.store') }} method="POST">
                    @csrf
                    <input type="hidden" name="aircraft_id" value="{{ $miss['ac']->id }}">
                    <input type="hidden" name="flight_id" value="{{ optional($miss['flt'])->id }}">
                    <input type="hidden" name="dpt_airport_id" value="{{ $miss['dep']->id }}">
                    <input type="hidden" name="arr_airport_id" value="{{ $miss['arr']->id }}">
                    <input type="hidden" name="mission_type" value="2">
                    <input type="hidden" name="mission_valid" value="{{ $miss['end'] }}">
                    <button type="submit" class="btn btn-sm btn-success py-0 px-1">Accept Mission</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </table>
        </div>
        <div class="card-footer p-1 text-end small">Available Missions: {{ count($mt_missions) }}</div>
      </div>
    </div>    
    {{-- Rebase Flights --}}
    <div class="col">
      <div class="card mb-2">
        <div class="card-header p-1 fw-bold">
          <h5 class="m-1">
            Mission: Rebase Parked Aircraft
            <i class="fas fa-paper-plane float-end"></i>
          </h5>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-sm table-striped table-secondary mb-0">
            <tr>
              <th>Aircraft</th>
              <th>Company</th>
              <th>Flight Number</th>
              <th>Origin</th>
              <th>Destination</th>
              <th>Valid Until</th>
              <th>&nbsp;</th>
            </tr>
            @foreach($sc_missions as $miss)
              <tr>
                <td>{{ $miss['ac']->ident }}</td>
                <td>{{ $miss['ac']->airline->name }}</td>
                <td>
                  @if(filled($miss['flt']))
                    {{ $miss['flt']->airline->code.' '.$miss['flt']->flight_number }}
                  @else
                    {{ '-' }}
                  @endif
                </td>
                <td>{{ $miss['dep']->id }}</td>
                <td>{{ $miss['arr']->id }}</td>
                <td>{{ $miss['end']->diffForHumans() }}</td>
                <td class="text-end">
                  <form action={{ route('DSpecial.missions.store') }} method="POST">
                    @csrf
                    <input type="hidden" name="aircraft_id" value="{{ $miss['ac']->id }}">
                    <input type="hidden" name="flight_id" value="{{ optional($miss['flt'])->id }}">
                    <input type="hidden" name="dpt_airport_id" value="{{ $miss['dep']->id }}">
                    <input type="hidden" name="arr_airport_id" value="{{ $miss['arr']->id }}">
                    <input type="hidden" name="mission_type" value="1">
                    <input type="hidden" name="mission_valid" value="{{ $miss['end'] }}">
                    <button type="submit" class="btn btn-sm btn-success py-0 px-1">Accept Mission</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </table>
        </div>
        <div class="card-footer p-1 text-end small">Available Missions: {{ count($sc_missions) }}</div>
      </div>
    </div>
  </div>
@endsection
