@extends('app')
@section('title', 'Missions')

@section('content')
  @if($my_missions->count() > 0)
    <div class="row row-cols-2 mb-2">
      {{-- Schedule Flights --}}
      <div class="col">
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
              </tr>
              @foreach($my_missions as $miss)
                <tr>
                  <td>{{ $miss->mission_order }}</td>
                  <td>{{ $miss->aircraft->ident }}</td>
                  <td>{{ $miss->flight->airline->name }}</td>
                  <td>{{ $miss->flight->airline->code.' '.$miss->flight->flight_number }}</td>
                  <td>{{ $miss->dpt_airport->id }}</td>
                  <td>{{ DS_FormatScheduleTime($miss->flight->dpt_time) }}</td>
                  <td>{{ DS_FormatScheduleTime($miss->flight->arr_time) }}</td>
                  <td>{{ $miss->arr_airport->id }}</td>
                  <td>{{ $miss->mission_valid->format('d.M.y H:i') }}</td>
                  <td>
                    <form action={{-- route('DSpecial.missions.store') --}} method="POST">
                      @csrf
                      <input type="hidden" name="id" value="{{ $miss->id }}">
                      <button type="submit" class="btn btn-sm btn-danger float-end py-0 px-1 disabled">Remove Mission</button>
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
    {{-- Schedule Flights --}}
    <div class="col">
      <div class="card mb-2">
        <div class="card-header p-1 fw-bold">
          <h5 class="m-1">
            Mission: Bring Aircraft Back | With Scheduled Flights
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
              <th>STD</th>
              <th>STA</th>
              <th>Destination</th>
              <th>Valid Until</th>
              <th>&nbsp;</th>
            </tr>
            @foreach($sc_missions as $miss)
              <tr>
                <td>{{ $miss['ac']->ident }}</td>
                <td>{{ $miss['flt']->airline->name }}</td>
                <td>{{ $miss['flt']->airline->code.' '.$miss['flt']->flight_number }}</td>
                <td>{{ $miss['flt']->dpt_airport_id }}</td>
                <td>{{ DS_FormatScheduleTime($miss['flt']->dpt_time) }}</td>
                <td>{{ DS_FormatScheduleTime($miss['flt']->arr_time) }}</td>
                <td>{{ $miss['flt']->arr_airport_id }}</td>
                <td>{{ $miss['end']->diffForHumans() }}</td>
                <td>
                  <form action={{ route('DSpecial.missions.store') }} method="POST">
                    @csrf
                    <input type="hidden" name="aircraft_id" value="{{ $miss['ac']->id }}">
                    <input type="hidden" name="flight_id" value="{{ $miss['flt']->id }}">
                    <input type="hidden" name="dpt_airport_id" value="{{ $miss['dep']->id }}">
                    <input type="hidden" name="arr_airport_id" value="{{ $miss['arr']->id }}">
                    <input type="hidden" name="mission_type" value="1">
                    <input type="hidden" name="mission_valid" value="{{ $miss['end'] }}">
                    <button type="submit" class="btn btn-sm btn-success float-end py-0 px-1">Accept Mission</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </table>
        </div>
        <div class="card-footer p-1 text-end small">Available Missions: {{ count($sc_missions) }}</div>
      </div>
    </div>
    {{-- Free Flights --}}
    <div class="col">
      <div class="card mb-2">
        <div class="card-header p-1 fw-bold">
          <h5 class="m-1">
            Mission: Bring Aircraft Back | With Free Flights
            <i class="fas fa-paper-plane float-end"></i>
          </h5>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-sm table-striped table-secondary mb-0">
            <tr>
              <th>Aircraft</th>
              <th>Company</th>
              <th>Origin</th>
              <th>Destination</th>
              <th>Valid Until</th>
              <th>&nbsp;</th>
            </tr>
            @foreach($fr_missions as $miss)
              <tr>
                <td>{{ $miss['ac']->ident }}</td>
                <td>{{ $miss['ac']->airline->name }}</td>
                <td>{{ $miss['dep']->id }}</td>
                <td>{{ $miss['arr']->id }}</td>
                <td>{{ $miss['end']->diffForHumans() }}</td>
                <td><a href="{{-- --}}" class="btn btn-sm btn-warning text-dark py-0 px-1 disabled">Create FreeFlight</a></td>
              </tr>
            @endforeach
          </table>
        </div>
        <div class="card-footer p-1 text-end small">Available Missions: {{ count($fr_missions) }}</div>
      </div>
    </div>
  </div>
@endsection
