@extends('app')
@section('title', 'Tour Details')
@php
  $units = isset($units) ? $units : DS_GetUnits();    
@endphp
@section('content')
  <div class="row">
    @include('DSpecial::tours.table')
    <div class="col-lg-2">
      <div class="nav flex-column nav-pills" id="pills-tab" role="tablist" aria-orientation="vertical">
        @if(filled($tour->tour_rules))
          <a class="nav-link mb-2 active" id="pills-legs-tab" data-toggle="pill" href="#pills-legs" role="tab" aria-controls="pills-legs" aria-selected="false">
            @lang('DSpecial::tours.legs')
          </a>
          <a class="nav-link mb-2" id="pills-rules-tab" data-toggle="pill" href="#pills-rules" role="tab" aria-controls="pills-rules" aria-selected="false">
            @lang('DSpecial::tours.trules')
          </a>
        @endif
        @if($tour->legs_count > 0)
          <button type="button" class="nav-link btn btn-sm mb-2 text-start" data-bs-toggle="modal" data-bs-target="#staticBackdrop" onclick="ExpandTourMap()">
            @lang('DSpecial::tours.tmap')
          </button>
        @endif
        @ability('admin', 'admin-access')
          <a class="nav-link mb-2" id="pills-report-tab" data-toggle="pill" href="#pills-report" role="tab" aria-controls="pills-report" aria-selected="false">
            @lang('DSpecial::tours.treport')
          </a>
        @endability
      </div>
    </div>
  </div>

  <div class="tab-content" id="pills-tabContent">
    {{-- Legs --}}
    <div class="tab-pane fade show active" id="pills-legs" role="tabpanel" aria-labelledby="pills-legs-tab">
      <div class="row">
        <div class="col">
          <div class="card mb-2">
            <div class="card-header p-1">
              <h5 class="m-1">
                @lang('DSpecial::tours.legs')
                <i class="fas fa-map-marked-alt float-end"></i>
              </h5>
            </div>
            <div class="card-body table-responsive p-0">
              @include('DSpecial::tours.legs_table')
            </div>
            <div class="card-footer p-0 px-1 text-end small fw-bold">
              @lang('DSpecial::tours.tlegs') : {{ $tour->legs->count() }}
            </div>
          </div>
        </div>
      </div>
    </div>
    {{-- Rules --}}
    @if(filled($tour->tour_rules))
      <div class="tab-pane fade" id="pills-rules" role="tabpanel" aria-labelledby="pills-rules-tab">
        <div class="row">
          <div class="col">
            <div class="card mb-2">
              <div class="card-header p-1">
                <h5 class="m-1">
                  @lang('DSpecial::tours.trules')
                  <i class="fas fa-book float-end"></i>
                </h5>
              </div>
              <div class="card-body p-1">
                {!! $tour->tour_rules !!}
              </div>
            </div>
          </div>
        </div>
      </div>
    @endif
    {{-- Report --}}
    @ability('admin', 'admin-access')
      <div class="tab-pane fade" id="pills-report" role="tabpanel" aria-labelledby="pills-report-tab">
        <div class="row">
          <div class="col">
            <div class="card mb-2">
              <div class="card-header p-1">
                <h5 class="m-1">
                  @lang('DSpecial::tours.treport')
                  <i class="fas fa-file-prescription float-end"></i>
                </h5>
              </div>
              <div class="card-body table-responsive p-0">
                @include('DSpecial::tours.report_table')
              </div>
            </div>
          </div>
        </div>
      </div>
    @endability
  </div>

  {{-- Map Modal --}}
  @if($tour->legs_count > 0)
    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-xxl mx-auto" style="width: 80vw; min-width: 80vw">
        <div class="modal-content">
          <div class="modal-header p-1 border-0">
            <h5 class="m-1" id="staticBackdropLabel">
              @lang('DSpecial::tours.tmap')
            </h5>
            <button type="button" class="btn-close mx-1" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0">
            <div id="tourmap" style="width: 100%; height: 82vh;"></div>
          </div>
          <div class="modal-footer border-0 p-0 px-1 text-end small fw-bold">
            <span class="mx-1">Airports: {{ count($mapAirports) }}, @lang('DSpecial::tours.legs'): {{ count($mapFlights) }}</span>
            <button type="button" class="btn btn-sm btn-warning p-0 px-1" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  @endif
@endsection

@section('scripts')
  @parent
  @if($tour->legs_count > 0)
    <script type="text/javascript">
      function ExpandTourMap() {
        // Define Icons
        var vmsIcon = new L.Icon({!! $mapIcons['vmsIcon'] !!});
        var RedIcon = new L.Icon({!! $mapIcons['RedIcon'] !!});
        var GreenIcon = new L.Icon({!! $mapIcons['GreenIcon'] !!});
        var BlueIcon = new L.Icon({!! $mapIcons['BlueIcon'] !!});
        var YellowIcon = new L.Icon({!! $mapIcons['YellowIcon'] !!});
        // Define Geodesic Line Colors
        var Flown = 'darkgreen';
        var NotFlown = 'darkred';
        var CheckDisabled = 'crimson';
        // Build Airports Layer
        var mAirports = L.layerGroup();
        @foreach ($mapAirports as $airport)
          var APT_{{ $airport['id'] }} = L.marker([{{ $airport['loc'] }}], {icon: {{ $airport['icon'] }} , opacity: 0.8}).bindPopup({!! "'".$airport['pop']."'" !!}).addTo(mAirports);
        @endforeach
        // Build Flights (Legs) Layer
        var mFlights = L.layerGroup();
        @foreach ($mapFlights as $flight)
          var FLT_{{ $flight['id'] }} = L.geodesic({{ $flight['geod'] }}, {weight: 2, opacity: 0.8, steps: 5, color: {{ $flight['geoc'] }}}).bindPopup({!! "'".$flight['pop']."'" !!}).addTo(mFlights);
        @endforeach
        // Define Base Layers For Control Box
        var DarkMatter = L.tileLayer.provider('CartoDB.DarkMatter');
        var NatGeo = L.tileLayer.provider('Esri.NatGeoWorldMap');
        var OpenSM = L.tileLayer.provider('OpenStreetMap.Mapnik');
        var OpenTopo = L.tileLayer.provider('OpenTopoMap');
        var WorldTopo = L.tileLayer.provider('Esri.WorldTopoMap');
        // Define Control Groups
        var BaseLayers = {"Dark Matter": DarkMatter, "NatGEO World": NatGeo, "OpenSM Mapnik": OpenSM, "Open Topo": OpenTopo, "World Topo": WorldTopo};
        var Overlays = {"Tour Airports": mAirports, "Tour Legs": mFlights};
        // Define Map and Add Control Box
        var TourMap = L.map('tourmap', {center: {{ $mapCenter }}, zoom: 4, layers: [DarkMatter, mAirports, mFlights]});
        L.control.layers(BaseLayers, Overlays).addTo(TourMap);
        setTimeout(function(){ TourMap.invalidateSize()}, 300);
      }
    </script>
  @endif
@endsection
