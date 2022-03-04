@extends('app')
@section('title', 'Tours')
@php
  $units = isset($units) ? $units : DS_GetUnits();
  $carbon_now = Carbon::now();
@endphp
@section('content')
  @if(!$tours->count())
    <div class="alert alert-info p-1 fw-bold">No Tours Found!</div>
  @else
    <ul class="nav nav-pills nav-justified mb-3" id="pills-tab" role="tablist">
      <li class="nav-item mx-1" role="presentation">
        <a class="nav-link p-1 active" id="pills-activet-tab" data-toggle="pill" href="#pills-activet" role="tab" aria-controls="pills-activet" aria-selected="true">
          @lang('DSpecial::tours.current')
        </a>
      </li>
      <li class="nav-item mx-1" role="presentation">
        <a class="nav-link p-1" id="pills-futuret-tab" data-toggle="pill" href="#pills-futuret" role="tab" aria-controls="pills-futuret" aria-selected="false">
          @lang('DSpecial::tours.future')
        </a>
      </li>
      <li class="nav-item mx-1" role="presentation">
        <a class="nav-link p-1" id="pills-closedt-tab" data-toggle="pill" href="#pills-closedt" role="tab" aria-controls="pills-closedt" aria-selected="false">
          @lang('DSpecial::tours.past')
        </a>
      </li>
      <li class="nav-item mx-1" role="presentation">
        <a class="nav-link p-1" id="pills-rulest-tab" data-toggle="pill" href="#pills-rulest" role="tab" aria-controls="pills-rulest" aria-selected="false">
          @lang('DSpecial::tours.trules')
        </a>
      </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
      <div class="tab-pane fade show active" id="pills-activet" role="tabpanel" aria-labelledby="pills-activet-tab">
        <div id="activet" class="row row-cols-lg-3">
          @foreach($tours as $tour)
            @if($carbon_now >= $tour->start_date && $carbon_now <= $tour->end_date)
              @include('DSpecial::tours.table')
            @endif
          @endforeach
        </div>
      </div>
      <div class="tab-pane fade" id="pills-futuret" role="tabpanel" aria-labelledby="pills-futuret-tab">
        <div id="futuret" class="row row-cols-lg-3">
          @foreach ($tours as $tour)
            @if($carbon_now < $tour->start_date)
              @include('DSpecial::tours.table')
            @endif
          @endforeach
        </div>
      </div>
      <div class="tab-pane fade" id="pills-closedt" role="tabpanel" aria-labelledby="pills-closedt-tab">
        <div id="closedt" class="row row-cols-lg-3">
          @foreach ($tours as $tour)
            @if($carbon_now > $tour->end_date)
              @include('DSpecial::tours.table')
            @endif
          @endforeach
        </div>
      </div>
      <div class="tab-pane fade" id="pills-rulest" role="tabpanel" aria-labelledby="pills-rulest-tab">
        <div id="rulest" class="row">
          <div class="col">
            <div class="card mb-2">
              <div class="card-header p-1">
                <h5 class="m-1">
                  Tour Rules
                  <i class="fas fa-question-circle float-end"></i>
                </h5>
              </div>
              <div class="card-body p-1">
                <p>&bull;&nbsp;Tours can be flown and reported either manually or with acars support, for acars supported tour flights pilots can either bid/load a flight from the list or enter required info manually to
                New Flight window of our acars software. While sending a manual pirep or using acars with manual flight info entry, please do not forget to add correct route code and leg number to your reports. Missing this step may cause problems during route leg checks and award controls.</p>
                <p>&bull;&nbsp;<b>Open Tours</b>&nbsp; can be flown with any airline and aircraft according to pilot's choice, simply there are no company and/or aircraft restrictions for this type. While on the other hand <b>Airline Tours</b> must be flown with correct airline callsign and if provided with the subfleet assigned to the leg.</p>
                <p>&bull;&nbsp;As a general rule, all tour legs must be completed between validity period for earning awards.</p>
                <p>&bull;&nbsp;<b>To see the details and legs of a tour, simply click on the Tour Name</b></p>
                <p>Safe Flights</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endif
@endsection
