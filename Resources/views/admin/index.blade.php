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
        <p>@if(filled($details->version)) Version: {{ $details->version }} @endif <a href="https://github.com/FatihKoz" target="_blank">&copy; B.Fatih KOZ</a></p>
      </div>
    </div>
  </div>

  <div class="row text-center" style="margin-left:5px; margin-right:5px;">
    <div class="col-sm-12">
      <h5 style="margin:5px; padding:5px;"><b>Handy Tools</b></h5>
    </div>
  </div>

  <div class="row text-center" style="margin-left:5px; margin-bottom:0px; margin-right:5px;">
      {{-- Left --}}
      <div class="col-md-4">
        <div class="card border-blue-bottom" style="padding:5px;">
          <b>Calculate Great Circle Distances</b>
          <br>
          <a class="btn btn-sm btn-primary" href="{{ route('DSpecial.admin') }}?action=dist">Missing Only</a>
          <a class="btn btn-sm btn-warning" title="Warning! This may take time..." href="{{ route('DSpecial.admin') }}?action=distall">All Flights</a>
          <br>
          <span class="text-info">Missing Only: null or 1 values are considered</span>
        </div>
        <div class="card border-blue-bottom" style="padding:5px;">
          <b>Calculate Flight Times</b>
          <br>
          <a class="btn btn-sm btn-primary mx-2" href="{{ route('DSpecial.admin') }}?action=ftime">Missing Only</a>
          <a clasS="btn btn-sm btn-warning mx-2" title="Warning! This may take time..." href="{{ route('DSpecial.admin') }}?action=ftimeall">All Flights</a>
          <br>
          <span class="text-info">Missing Only: null or 1 values are considered</span>
        </div>
        <div class="card border-blue-bottom" style="padding:5px;">
          <b>SimBrief Packs and Bids</b>
          <br>
          <a class="btn btn-sm btn-primary" href="{{ route('DSpecial.admin') }}?action=cleansb">SB Clean Old</a>
          <a class="btn btn-sm btn-warning" href="{{ route('DSpecial.admin') }}?action=cleansball">SB Clean ALL</a>
          <a class="btn btn-sm btn-danger" href="{{ route('DSpecial.admin') }}?action=fixpsb">SB Fix Problems</a>
          <a class="btn btn-sm btn-primary" href="{{ route('DSpecial.admin') }}?action=cleanbids">BIDS Clean Old</a>
          <br>
          <span class="text-info">Old: <b>+3 Hours (SB)</b> and no pireps tied, <b>+24 Hours (BIDS)</b></span>
        </div>
      </div>
      {{-- Middle --}}
      <div class="col-md-4">
        <div class="card border-blue-bottom" style="padding:5px;">
          <b>Manual Backups</b>
          <br>
          <a class="btn btn-sm btn-primary" href="{{ route('DSpecial.admin') }}?action=backupdata">Database Only</a>
          <a class="btn btn-sm btn-primary" href="{{ route('DSpecial.admin') }}?action=backupfile">Files Only</a>
          <a class="btn btn-sm btn-warning" title="Warning! This may take time..." href="{{ route('DSpecial.admin') }}?action=backupfull">Full Backup</a>
          <a class="btn btn-sm btn-danger" href="{{ route('DSpecial.admin') }}?action=backupclean">Clean Backups</a>
          <br>
          <span class="text-info">Use only if cron fails or when an urgent backup is needed.</span>
        </div>
        <div class="card border-blue-bottom" style="padding:5px;">
          <a class="btn btn-sm btn-primary" href="{{ route('DSpecial.admin') }}?action=returnbase">Return Aircraft To Their Hubs</a>
          <br>
          <span class="text-info">Aircraft with hub definitions, left over at airports with no movement for <b>last 3 days</b> are moved!</span>
        </div>
      </div>
      {{-- Right --}}
      <div class="col-md-4">
        <div class="card border-blue-bottom" style="padding:5px;">
          <b>Adjust Airport Fuel Prices</b>
          <br>
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
          <span class="text-info">100 = no change, 103.5 = %3.5 increase, 95 = %5 decrease</span>
          <br>
          <a href="https://www.iata.org/en/publications/economics/fuel-monitor/" target="_blank">IATA Fuel Monitor</a>
        </div>
        @if ($diversions && $diversions->count() > 0)
          <div class="card border-blue-bottom" style="padding:5px;">
            <b>Fix Diversions</b>
            <br>
            @foreach ($diversions as $diversion)
              &bull;
              {{ $diversion->ident }} |
              {{ optional($diversion->aircraft)->registration }} |
              {{ optional($diversion->user)->name_private }} |
              {{ $diversion->alt_airport_id }} > {{ $diversion->arr_airport_id }} |
              <a href="{{ route('DSpecial.admin') }}?action=fixdiversion&divp={{ $diversion->id }}">Click to Fix</a>
              <br>
            @endforeach
            <br>
            <span class="text-info">Pilot and Aircraft will be moved to intented destination.<br>PIREP Arrival Airport will be corrected.<br>Only Diversions of <b>last 7 Days</b> are listed</span>
          </div>
        @endif
      </div>
  </div>

  <div class="row text-center" style="margin-left:5px; margin-right:5px;">
    <div class="col-sm-12">
      <h5 style="margin:5px; padding:5px;"><b>Module Features</b></h5>
    </div>
    <div class="col-sm-12">
      <a class="btn btn-primary" href="{{ route('DSpecial.tour_admin') }}">Tours Management</a>
      <a class="btn btn-primary" href="{{ route('DSpecial.notam_admin') }}">Notams Management</a>
      <a class="btn btn-primary" href="{{ route('DSpecial.market_admin') }}">Market Management</a>
      <a class="btn btn-primary" href="{{ route('DSpecial.maint_admin') }}">Maintenance Management</a>
    </div>
  </div>

  <div class="row text-center" style="margin-left:5px; margin-right:5px;">
    <div class="col-sm-12">
      <h5 style="margin:5px; padding:5px;"><b>Module Settings</b></h5>
    </div>
    <div class="col-md-6">
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Discord Notifications & Diversion Handling</b>
        <br>
        @include('DSpecial::admin.settings_table', ['group' => 'Discord'])
        <span class="text-info">Create your ADMIN ONLY webhook before enabling it here</span>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Cron Features & Database Cleanup</b>
        <br>
        @include('DSpecial::admin.settings_table', ['group' => 'Cron'])
        <span class="text-info">Setting 0 as day value will disable the feature</span>
      </div>
    </div>
  </div>

  <div class="row text-center" style="margin-left:5px; margin-right:5px;">
    <div class="col-md-6">
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Random Flights Rewards</b>
        <br>
        @include('DSpecial::admin.settings_table', ['group' => 'Random Flights'])
        <br>
        <span class="text-info">Pilot's Rank Pay Rate or Flight's Pilot Pay will be multiplied</span>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Disposable Free Flights</b>
        <br>
        @include('DSpecial::admin.settings_table', ['group' => 'Free Flights'])
      </div>
    </div>
  </div>

  <div class="row text-center" style="margin-left:5px; margin-right:5px;">
    <div class="col-sm-12">
      <h5 style="margin:5px; padding:5px;"><b>Custom Income and Expense Settings</b></h5>
    </div>
  </div>

  <div class="row text-center" style="margin-left:5px; margin-right:5px;">
    <div class="col-md-4">
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Income</b>
        <br>
        @include('DSpecial::admin.settings_table', ['group' => 'Income'])
      </div>
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Handling</b>
        <br>
        @include('DSpecial::admin.settings_table', ['group' => 'Ground Handling'])
      </div>
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Terminal</b>
        <br>
        @include('DSpecial::admin.settings_table', ['group' => 'Terminal Services'])
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Fuel</b>
        <br>
        @include('DSpecial::admin.settings_table', ['group' => 'Fuel Services'])
      </div>
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Authority</b>
        <br>
        @include('DSpecial::admin.settings_table', ['group' => 'Airport Authority'])
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Landing</b>
        <br>
        @include('DSpecial::admin.settings_table', ['group' => 'Landing Fee'])
      </div>
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Parking</b>
        <br>
        @include('DSpecial::admin.settings_table', ['group' => 'Parking Fee'])
      </div>
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Enroute</b>
        <br>
        @include('DSpecial::admin.settings_table', ['group' => 'Enroute'])
        <span class="text-info">Air Traffic Control, Overflight Cost etc.</span>
      </div>
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Catering</b>
        <br>
        @include('DSpecial::admin.settings_table', ['group' => 'Catering'])
      </div>
    </div>
  </div>

  <div class="row text-center" style="margin-left:5px; margin-right:5px;">
    <div class="col-sm-12">
      <h5 style="margin:5px; padding:5px;"><b>Maintenance and Flight Assignments</b></h5>
    </div>
  </div>

  <div class="row text-center" style="margin-left:5px; margin-right:5px;">
    <div class="col-md-6">
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Maintenance Settings</b>
        <br>
        @include('DSpecial::admin.settings_table', ['group' => 'Maintenance'])
      </div>
    </div>
    <div class="col-md-6">
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>Monthly Flight Assignments</b>
        <br>
        @include('DSpecial::admin.settings_table', ['group' => 'Assignments'])
      </div>
      {{-- API Service Key Group --}}
      <div class="card border-blue-bottom" style="padding:5px;">
        <b>API Services</b>
        <br>
        @include('DSpecial::admin.settings_table', ['group' => 'API Service'])
        <span class="text-info">Service Key is needed to authorize API requests for DispoSpecial Features (assignments and tours)<br>Same service key is shared between Disposable Modules</span>
      </div>
    </div>
  </div>

  @if(config('app.name') === 'TurkSim')
    <div class="row text-center" style="margin-left:5px; margin-right:5px;">
      <div class="col-sm-6">
        <div class="card border-blue-bottom" style="padding:5px;">
          <b>{{ config('app.name') }} Special Settings</b>
          <br>
          @include('DSpecial::admin.settings_table', ['group' => config('app.name')])
        </div>
      </div>
    </div>
  @endif

@endsection
