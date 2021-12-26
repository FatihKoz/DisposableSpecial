@extends('app')
@section('title', 'Event Details')

@section('content')
  <div class="row">
    @include('DSpecial::events.table')
    <div class="col-2">
      <div class="nav flex-column nav-pills" id="pills-tab" role="tablist" aria-orientation="vertical">
        <button type="button" class="nav-link btn btn-sm mb-2 text-start" data-bs-toggle="modal" data-bs-target="#staticBackdrop" onclick="ExpandEventMap()">
          Event Map
        </button>
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
                Event Legs
                <i class="fas fa-map-marked-alt float-end"></i>
              </h5>
            </div>
            <div class="card-body table-responsive p-0">
              @include('DSpecial::events.legs_table')
            </div>
            <div class="card-footer p-0 px-1 text-end small fw-bold">
              @lang('DSpecial::tours.tlegs') : {{ $event->flights->count() }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Map Modal --}}
  @if($event->flights->count())
    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-xxl" style="width: 80vw; min-width: 80vw">
        <div class="modal-content">
          <div class="modal-header p-1 border-0">
            <h5 class="m-1" id="staticBackdropLabel">
              Event Map
            </h5>
            <button type="button" class="btn-close mx-1" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0">
            <div id="eventmap" style="width: 100%; height: 82vh;"></div>
          </div>
          <div class="modal-footer border-0 p-0 px-1 text-end small fw-bold">
            <span class="mx-1">Airports: {{ count($mapAirports) }}, Event Legs: {{ count($mapFlights) }}</span>
            <button type="button" class="btn btn-sm btn-warning p-0 px-1" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  @endif
@endsection

@section('scripts')
  @parent
  @if($event->flights->count())
    <script type="text/javascript">
      function ExpandEventMap() {
        // Define Icons
        var vmsIcon = new L.Icon({!! $mapIcons['vmsIcon'] !!});
        var RedIcon = new L.Icon({!! $mapIcons['RedIcon'] !!});
        var GreenIcon = new L.Icon({!! $mapIcons['GreenIcon'] !!});
        var BlueIcon = new L.Icon({!! $mapIcons['BlueIcon'] !!});
        var YellowIcon = new L.Icon({!! $mapIcons['YellowIcon'] !!});
        // Define Geodesic Line Colors
        var Flown = 'darkgreen';
        var NotFlown = 'darkred';
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
        var Overlays = {"Event Airports": mAirports, "Event Legs": mFlights};
        // Define Map and Add Control Box
        var EventMap = L.map('eventmap', {center: {{ $mapCenter }}, zoom: 4, layers: [DarkMatter, mAirports, mFlights]});
        L.control.layers(BaseLayers, Overlays).addTo(EventMap);
        setTimeout(function(){ EventMap.invalidateSize()}, 300);
      }
    </script>
  @endif
@endsection
