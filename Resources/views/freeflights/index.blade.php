@extends('app')
@section('title', 'My Flight')

@section('content')
  <div class="row mb-2">
    <div class="col">
      {{ Form::open(array('route' => 'DSpecial.freeflight_store', 'method' => 'post')) }}
        <div class="card mb-2">
          <div class="card-header p-1" >
            <h5 class="m-1">
              @lang('DSpecial::common.myflight')
              <i class="fas fa-paper-plane float-end"></i>
            </h5>
          </div>
          <div class="card-body p-2 text-start">
            <div class="row row-cols-xl-4 mb-2">
              {{-- Airline & Flight Number --}}
              <div class="col-lg">
                <div class="input-group input-group-sm">
                  <span class="input-group-text" title="@lang('common.airline') & @lang('flights.flightnumber')"><i class="fas fa-paper-plane"></i></span>
                  @if($airlines->count() > 1 && !$settings['pilot_company'])
                    <select id="airline_selection" name="ff_airlineid" class="form-control select2" onchange="ChangeCallsignICAO()">
                      @foreach($airlines as $airline)
                        <option @if($user->airline_id == $airline->id) selected @endif value="{{ $airline->id }}">{{ '['.$airline->code.'] '.$airline->name }}</option>
                      @endforeach
                    </select>
                  @else
                    <input type="hidden" name="ff_airlineid" value="{{ $user->airline_id }}">
                    <span class="input-group-text">{{ optional($user->airline)->icao }}</span>
                  @endif
                  <input type="number" name="ff_number" class="form-control" value="{{ $fflight->flight_number }}" min="0" max="9999">
                </div>
              </div>
              {{-- Callsign --}}
              @if(!$settings['sb_callsign'])
                <div class="col-lg">
                  <div class="input-group input-group-sm">
                    <span class="input-group-text" title="@lang('flights.callsign') @lang('DSpecial::common.optional')">
                      <i class="fas fa-headset"></i>
                    </span>
                    <span class="input-group-text" id="callsign_icao">{{ optional($user->airline)->icao }}</span>
                    <input type="text" name="ff_callsign" class="form-control" value="{{ $fflight->callsign ?? $user->callsign}}" placeholder="{{$user->id}}DH" maxlength="4">
                  </div>
                </div>
              @endif
            </div>
            <div class="row row-cols-xl-4 mb-2">
              {{-- Departure Aerodrome --}}
              <div class="col-md">
                <div class="input-group input-group-sm">
                  <span class="input-group-text" title="@lang('airports.departure')"><i class="fas fa-plane-departure"></i></span>
                  <input type="text" name="ff_orig" class="form-control" maxlength="4"
                        value="{{ $user->curr_airport_id ?? $user->home_airport_id }}"
                        @if($settings['pilot_location']) readonly @endif>
                </div>
              </div>
              {{-- Arrival Aerodrome --}}
              <div class="col-md">
                <div class="input-group input-group-sm">
                  <span class="input-group-text" title="@lang('airports.arrival')"><i class="fas fa-plane-arrival"></i></span>
                  <input type="text" name="ff_dest" class="form-control" value="{{ $fflight->arr_airport_id }}" maxlength="4">
                </div>
              </div>
              {{-- IATA Flight Type --}}
              <div class="col-xl">
                <div class="input-group input-group-sm">
                  <span class="input-group-text" title="IATA Flight Type"><i class="fas fa-atlas"></i></span>
                  <select id="type_selection" name="ff_iatatype" class="form-control select2">
                    <option value="E">Select Flight Type (Optional)</option>
                    @foreach($flight_types as $key => $name)
                      @if(in_array($key, ['C', 'E', 'H', 'K', 'O', 'P', 'T']))
                        <option value="{{ $key }}" @if($fflight->flight_type == $key) selected @endif>{{ $name }}</option>
                      @endif
                    @endforeach
                  </select>
                </div>
              </div>
              {{-- Equipment --}}
              @if($aircraft->count())
                <div class="col-xl">
                  <div class="input-group input-group-sm">
                    <span class="input-group-text" title="@lang('common.aircraft') @lang('DSpecial::common.optional')">
                      <i class="fas fa-plane"></i>
                    </span>
                    <select id="aircraft_selection" name="ff_aircraft" class="form-control select2">
                      <option value="0">@lang('DSpecial::common.selectac') @lang('DSpecial::common.optional')</option>
                      @foreach($aircraft as $ac)
                        <option value="{{ $ac->id }}">
                          {{ $ac->ident }}
                          @if($ac->fuel_onboard[$units['fuel']] > 0)
                            {{ ' | '.__('DSpecial::common.fuelob').': '.DS_ConvertWeight($ac->fuel_onboard, $units['fuel']) }}
                          @endif
                        </option>
                      @endforeach
                    </select>
                  </div>
                </div>
              @endif
            </div>
          </div>
          <div class="card-footer p-1 text-end">
            <input type="hidden" name="ff_id" value="{{ $fflight->id }}">
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <input type="hidden" name="ff_owner" value="@if(Theme::getSetting('roster_ident')) {{ $user->ident.' - ' }} @endif {{ $user->name_private }}">
            <button class="btn btn-sm btn-primary p-0 px-1" type="submit">@lang('DSpecial::common.ff_button')</button>
          </div>
        </div>
      {{ Form::close() }}
    </div>
  </div>

  <div class="row row-cols-xl-2">
    <div class="col-xl-6">
      {{-- Empty Column For Spacing --}}
    </div>
    <div class="col-xl-6">
      <div class="card mb-2">
        <div class="card-header p-1">
          <h5 class="m-1">
            Instructions For Free Flights
            <i class="fas fa-question float-end"></i>
          </h5>
        </div>
        <div class="card-body p-1 text-start">
          <p>After changing details regarding your Personal Flight just click <b>Update & Proceed</b> button.
          <br>Your flight will be updated and your Bid will be placed...</p>
          <p>As like other flights provided by our system, you will be able to generate a SimBrief OFP for your Personal/Free Flight.</p>
          <p>When you are ready for flight, just run Acars software and click <b>Search/Bids</b> button, then click <b>Bids</b>.
          <br>You will find your Personal/Free Flight always under Bids, it will not be visible via search or at website.</p>
          <p>Load your bidded flight and continue operation ... Safe flights</p>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  @parent
  <script type="text/javascript">
    // Change Callsign ICAO According To User's Airline Selection
    var ICAO = new Array();
    @foreach($airlines as $airline)
      ICAO[{{ $airline->id }}] = '{{ $airline->icao }}';
    @endforeach
    function ChangeCallsignICAO() {
      let selected_airline = document.getElementById('airline_selection').value;
      document.getElementById('callsign_icao').innerHTML = ICAO[selected_airline];
    }
  </script>
@endsection
