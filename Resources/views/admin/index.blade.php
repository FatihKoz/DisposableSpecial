@extends('admin.app')
@section('title', 'Disposable Special')

@section('content')
  <div class="row" style="margin-left:5px; margin-right:5px;">
    <div class="card border-blue-bottom" style="margin-left:5px; margin-right:5px; margin-bottom:5px;">
      <div class="content">
        <p>
          This module is designed to provide extended features to your phpVMS v7 along with some handy functions.<br>
        </p>
        <p><b>Details about the module can be found in the README.md file</b></p>
        <p>&bull; <a href="https://github.com/FatihKoz/DisposableSpecial#readme" target="_blank">Online Readme</a></p>
        <hr>
        <p><a href="https://github.com/FatihKoz" target="_blank">&copy; B.Fatih KOZ</a></p>
      </div>
    </div>
  </div>

  <div class="row text-center" style="margin-left:5px; margin-right:5px;">
    <div class="col-sm-12">
      <h5 style="margin:5px; padding:5px;"><b>Admin Functions</b></h5>
    </div>
  </div>

  <div class="row text-center" style="margin-left:5px; margin-right:5px;">
      {{-- Left --}}
      <div class="col-sm-4">
        <div class="card border-blue-bottom" style="padding:5px;">
          <a href="{{ route('DSpecial.admin') }}?action=dist">Calculate Distances For Flights</a>
          <br><br>
          <span class="text-info">Only flights with no (null or 1 nm) distances are affected.</span>
        </div>
        <div class="card border-blue-bottom" style="padding:5px;">
          <a href="{{ route('DSpecial.admin') }}?action=ftime">Calculate Flight Times</a>
          <br><br>
          <span class="text-info">Only flights with no (null or 1 min) flight times are affected</span>
        </div>
        <div class="card border-blue-bottom" style="padding:5px;">
          <a href="{{ route('DSpecial.admin') }}?action=distall">Calculate Great Circle Distance FOR ALL FLIGHTS</a>
          <br><br>
          <span class="text-info"><b>WARNING ALL FLIGHTS WILL BE AFFECTED</b><br>Please be patient calculation may take some time.</span>
        </div>
        <div class="card border-blue-bottom" style="padding:5px;">
          <a href="{{ route('DSpecial.admin') }}?action=ftimeall">Calculate Flight Times FOR ALL FLIGHTS</a>
          <br><br>
          <span class="text-info"><b>WARNING ALL FLIGHTS WILL BE AFFECTED</b><br>Please be patient calculation may take some time.</span>
        </div>
      </div>
      {{-- Middle --}}
      <div class="col-sm-4">
        <div class="card border-blue-bottom" style="padding:5px;">
          <a href="{{ route('DSpecial.admin') }}?action=returnbase">Return Aircraft To Their Hubs</a>
          <br><br>
          <span class="text-info">Only aircraft which are left over at airports with no movement for the <b>last 7 days</b> are affected. Also aircraft or subfleet hub must be defined!</span>
        </div>
        <div class="card border-blue-bottom" style="padding:5px;">
          <a href="{{ route('DSpecial.tour_admin') }}">Tours Management</a>
          <br><br>
          <span class="text-info">Manage your tours, insert or edit here.<br>(Legs must be defined/edited via PhpVms Admin > Flights)</span>
        </div>
        <div class="card border-blue-bottom" style="padding:5px;">
          <a href="{{ route('DSpecial.notam_admin') }}">Notams Management</a>
          <br><br>
          <span class="text-info">Manage your notams, insert or edit here.</span>
        </div>
        <div class="card border-blue-bottom" style="padding:5px;">
          <a href="{{ route('DSpecial.maint_admin') }}">Maintenance Management</a>
          <br><br>
          <span class="text-info">Manage ongoing Maintenance operations, view overall fleet status.</span>
        </div>
        @if ($diversions && $diversions->count() > 0)
          <div class="card border-blue-bottom" style="padding:5px;">
            <b>Fix Diversions</b>
            <br><br>
            @foreach ($diversions as $diversion)
              &bull;
              {{ optional($diversion->airline)->code.$diversion->ident }} |
              {{ optional($diversion->aircraft)->registration }} |
              {{ optional($diversion->user)->name_private }} |
              {{ $diversion->alt_airport_id }} > {{ $diversion->arr_airport_id }} |
              <a href="{{ route('DSpecial.admin') }}?action=fixdiversion&divp={{ $diversion->id }}">Click to Fix</a>
              <br>
            @endforeach
            <br>
            <span class="text-info">Pilot and Aircraft will be moved to intented destination.<br>PIREP Arrival Airport will be corrected.<br>Only Diversion From <b>Last 7 Days</b> are listed</span>
          </div>
        @endif
      </div>
      {{-- Right --}}
      <div class="col-sm-4">
        <div class="card border-blue-bottom" style="padding:5px;">
          <b>SimBrief Packs:</b> 
          <a href="{{ route('DSpecial.admin') }}?action=cleansb"> Clean Old</a> | 
          <a href="{{ route('DSpecial.admin') }}?action=cleansball"> Clean ALL</a> | 
          <a href="{{ route('DSpecial.admin') }}?action=fixpsb"> Fix Problems</a>
          <br><br>
          <span class="text-info">Only SimBrief Packs older than <b>3 Hours</b> and have no pirep attachements will be deleted.</span>
        </div>
        <div class="card border-blue-bottom" style="padding:5px;">
          <a href="{{ route('DSpecial.admin') }}?action=cleanbids">Clean Old Bids</a>
          <br><br>
          <span class="text-info">Only Bids older than <b>24 Hours</b> will be deleted.</span>
        </div>
        <div class="card border-blue-bottom" style="padding:5px;">
          <b>Adjust Airport Fuel Prices</b>
          <br><br>
          <form action="{{ route('DSpecial.admin') }}" id="FuelPrice">
            <input type="hidden" name="action" value="fuelprice">
            <div class="row text-center">
              <div class="col-sm-6">
                <label for="pct">Change Percentage</label>
                <input class="form-control" type="number" id="pct" name="pct" value="100" step="0.01" min="0" max="500">
              </div>
              <div class="col-sm-6">
                <label for="FuelType">Select Fuel Type:</label>
                <select class="form-control" id="FuelType" name="ft">
                  <option value="0">100LL</option>
                  <option selected value="1">JET A-1</option>
                  <option value="2">MOGAS</option>
                </select>
              </div>
            </div>
            <input type="submit" value="Update Prices">
          </form>
          <br>
          <span class="text-info">100 = no change, 103.5 = %3.5 increase, 95 = %5 decrease</span>
          <br>
          <a href="https://www.iata.org/en/publications/economics/fuel-monitor/" target="_blank">IATA Fuel Monitor</a>
        </div>
      </div>
  </div>

  <div class="row text-center" style="margin-left:5px; margin-right:5px;">
    <div class="col-sm-12">
      <h5 style="margin:5px; padding:5px;"><b>Module Settings</b></h5>
    </div>
  </div>
  <div class="row text-center" style="margin-left:5px; margin-right:5px;">
    <div class="col-sm-8">
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Discord Notifications & Diversion Handling</b>
        <br><br>
        @include('DSpecial::admin.settings_table', ['group' => 'Discord'])
        <span class="text-info">Create your ADMIN ONLY webhook before enabling it here, also check laravel logs if the messages do not appear at your Discord Server</span>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Random Flights Rewards</b>
        <br><br>
        @include('DSpecial::admin.settings_table', ['group' => 'Random Flights'])
        <br>
        <span class="text-info">Pilot's Rank Pay Rate or Flight's Pilot Pay will be multiplied</span>
      </div>
    </div>
  </div>

  <div class="row text-center" style="margin-left:5px; margin-right:5px;">
    <div class="col-sm-4">
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Maintenance Settings</b>
        <br><br>
        @include('DSpecial::admin.settings_table', ['group' => 'Maintenance'])
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Fuel</b>
        <br><br>
        @include('DSpecial::admin.settings_table', ['group' => 'Fuel Services'])
      </div>
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Authority</b>
        <br><br>
        @include('DSpecial::admin.settings_table', ['group' => 'Airport Authority'])
      </div>
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Handling</b>
        <br><br>
        @include('DSpecial::admin.settings_table', ['group' => 'Ground Handling'])
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Landing</b>
        <br><br>
        @include('DSpecial::admin.settings_table', ['group' => 'Landing Fee'])
      </div>
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Parking</b>
        <br><br>
        @include('DSpecial::admin.settings_table', ['group' => 'Parking Fee'])
      </div>
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Enroute</b>
        <br><br>
        @include('DSpecial::admin.settings_table', ['group' => 'Enroute'])
        <span class="text-info">Air Traffic Control, Overflight Cost etc.</span>
      </div>
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Terminal</b>
        <br><br>
        @include('DSpecial::admin.settings_table', ['group' => 'Terminal Services'])
      </div>
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Catering</b>
        <br><br>
        @include('DSpecial::admin.settings_table', ['group' => 'Catering'])
      </div>
    </div>
  </div>

  <div class="row text-center" style="margin-left:5px; margin-right:5px;">
    <div class="col-sm-6">
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Income</b>
        <br><br>
        @include('DSpecial::admin.settings_table', ['group' => 'Income'])
      </div>
    </div>
    <div class="col-sm-6">
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Monthly Flight Assignments</b>
        <br><br>
        @include('DSpecial::admin.settings_table', ['group' => 'Assignments'])
      </div>
    </div>
  </div>

  @if(config('app.name') === 'TurkSim')
    <div class="row text-center" style="margin-left:5px; margin-right:5px;">
      <div class="col-sm-6">
        <div class="card border-blue-bottom" style="padding:5px;">
          <b>{{ config('app.name') }} Special Settings</b>
          <br><br>
          @include('DSpecial::admin.settings_table', ['group' => config('app.name')])
        </div>
      </div>
    </div>
  @endif

@endsection
