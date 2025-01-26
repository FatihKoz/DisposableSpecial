@extends('admin.app')
@section('title', 'Disposable Tours')

@section('content')
  <div class="card border-blue-bottom" style="margin-left:5px; margin-right:5px; margin-bottom:5px;">
    <div class="content">
      <p>Only Tour Details are defined and edited here, legs must be inserted from Admin/Flights or better should be csv imported to ease the process.</p>
      <p>Tour Code defined here <b>MUST MATCH</b> the flight/route code and flight/route legs must be defined for tours to work properly.</p>
      <p>Active tours will be displayed at Tours page according to Start and End dates provided here.</p>
      <p>&nbsp;</p>
      <p><a href="https://github.com/FatihKoz" target="_blank">&copy; B.Fatih KOZ</a></p>
    </div>
  </div>
  <div class="row text-center" style="margin: 5px;">
    <h4 style="margin: 5px; padding:0px;"><b>Tour Management</b></h4>
  </div>
  <div class="row" style="margin-left:5px; margin-right:5px;">
    <div class="card border-blue-bottom" style="padding:10px;">
      <form class="form" method="post" action="{{ route('DSpecial.tour_store') }}">
        @csrf
        <input type="hidden" name="id" value="{{ $tour->id ?? '' }}">
        <div class="row" style="margin-bottom: 10px;">
          <div class="col-sm-6">
            <label class="pl-1 mb-1" for="tour_id">Select Pre-Recorded Tour for Editing</label>
            <select id="tour_selection" class="form-control select2" onchange="checkselection()">
              <option value="0">Please Select A Tour</option>
              @foreach($alltours->sortBy('tour_name') as $tourlist)
                <option value="{{ $tourlist->id }}" @if($tour &&  $tour->id == $tourlist->id) selected @endif>{{ $tourlist->tour_name }} : {{ $tourlist->tour_code }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-sm-4 text-left align-middle"><br>
            <a id="edit_link" style="visibility: hidden" href="{{ route('DSpecial.tour_admin') }}" class="btn btn-primary pl-1 mb-1">Load Selected Tour For Edit</a>
          </div>
        </div>
        <div class="row" style="margin-bottom: 10px;">
          <div class="col-sm-6">
            <label class="pl-1 mb-1" for="tour_name">Tour Name *</label>
            <input name="tour_name" type="text" class="form-control" placeholder="Mandatory" maxlength="150" value="{{ $tour->tour_name ?? '' }}">
          </div>
          <div class="col-sm-2">
            <label class="pl-1 mb-1" for="tour_code">Tour Code *</label>
            <input name="tour_code" type="text" class="form-control" placeholder="Mandatory" maxlength="5" value="{{ $tour->tour_code ?? '' }}">
          </div>
          <div class="col-sm-2">
            <label class="pl-1 mb-1" for="start_date">Start Date *</label>
            <input name="start_date" type="text" class="form-control" placeholder="2021-03-15" value="{{ optional(optional($tour)->start_date)->format('Y-m-d') ?? '' }}">
          </div>
          <div class="col-sm-2">
            <label class="pl-1 mb-1" for="end_date">End Date *</label>
            <input name="end_date" type="text" class="form-control" placeholder="2021-06-30" value="{{ optional(optional($tour)->end_date)->format('Y-m-d') ?? '' }}">
          </div>
        </div>
        <div class="row" style="margin-bottom: 10px;">
          <div class="col-sm-8">
            <label class="pl-1 mb-1" for="tour_desc">Tour Description (Optional)</label>
            <textarea id="editor_desc" name="tour_desc" class="editor_desc">{!! $tour->tour_desc ?? '' !!}</textarea>
            <hr>
            <label class="pl-1 mb-1" for="tour_rules">Tour Rules (Optional)</label>
            <textarea id="editor_rules" name="tour_rules" class="editor_rules">{!! $tour->tour_rules ?? '' !!}</textarea>
          </div>
          <div class="col-sm-4">
            <label class="pl-1 mb-1" for="tour_airline">Airline</label>
            <select name="tour_airline" class="form-control select2">
              <option value="0">Select An Airline (Optional)</option>
              @foreach($airlines as $airline)
                <option value="{{ $airline->id }}" @if($tour && $tour->tour_airline == $airline->id) selected @endif>{{ $airline->icao }} | {{ $airline->name }}</option>
              @endforeach
            </select>
            <hr>
            <label class="pl-1 mb-1" for="tour_token">Token</label>
            <select name="tour_token" class="form-control select2">
              <option value="0">Select A Market Token (Optional)</option>
              @foreach($tokens as $token)
                <option value="{{ $token->id }}" @if($tour && $tour->tour_token == $token->id) selected @endif>{{ $token->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="row" style="margin-bottom: 10px;">
          <div class="col-sm-2 text-left">
            <input type="hidden" name="active" value="0">
            <label class="pl-1 mb-1" for="active">Active <input name="active" type="checkbox" @if($tour && $tour->active == 1) checked="true" @endif class="form-control" value="1"></label>
          </div>
          <div class="col-sm-10 text-right">
            @if(isset($tour) && blank($tour->legs))
              <button class="btn btn-danger pl-1 mb-1" type="submit" name="delete_tour" value="delete_tour" onclick="return confirm('Are you really sure ?\nThis action is irreversible !!!')">Delete Tour</button>
            @endif
            <button class="btn btn-primary pl-1 mb-1" type="submit">@if($tour && $tour->id) Update @else Save @endif</button>
          </div>
        </div>
      </form>
    </div>
    @if(filled(optional($tour)->legs))
      <div class="row text-center" style="margin: 5px;">
        <h4 style="margin: 5px; padding:0px;"><b>Tour Legs</b></h4>
      </div>
      <div class="card border-blue-bottom" style="padding:10px">
        <form class="form text-right" style="margin-bottom: 2px" method="post" action="{{ route('DSpecial.tour_legactions') }}">
          @csrf
          <input type="hidden" name="tour_id" value="{{ $tour->id }}">
          <input type="hidden" name="tour_code" value="{{ $tour->tour_code }}">
          <button class="btn btn-danger pl-1 mb-1" type="submit" name="button_delete" value="delete_all" onclick="return confirm('Are you really sure ?\nThis action is irreversible !!!')">Delete Legs</button>
          <button class="btn btn-secondary pl-1 mb-1" type="submit" name="button_clean" value="clean_all" onclick="return confirm('Are you really sure ?\nThis action is irreversible !!!')">Clean Notes/Remarks of Legs</button>
          <button class="btn btn-primary pl-1 mb-1" type="submit" name="button_activate" value="activate_all">Activate Legs</button>
          <button class="btn btn-warning pl-1 mb-1" type="submit" name="button_activate" value="deactivate_all">De-Activate Legs</button>
          <button class="btn btn-success pl-1 mb-1" type="submit" name="button_own" value="own_all">Set Ownership</button>
          <button class="btn btn-warning pl-1 mb-1" type="submit" name="button_own" value="drop_all">Drop Ownership</button>
        </form>
        <table class="table table-sm table-striped text-left mt-2 mb-0">
          <tr>
            <th>Flight Number</th>
            <th>Code</th>
            <th>Leg</th>
            <th>Origin</th>
            <th>Destination</th>
            <th>Distance</th>
            <th>Duration</th>
            <th class="text-center">Leg Dates (UTC)</th>
            <th class="text-center">Subfleets</th>
            <th>Notes</th>
            <th class="text-right">Details</th>
            <th class="text-right">Actions</th>
          </tr>
          @foreach($tour->legs->sortBy('route_leg', SORT_NATURAL) as $leg)
            <tr>
              <td>{{ optional($leg->airline)->code.' '.$leg->flight_number}}</td>
              <td>{{ $leg->route_code }}</td>
              <td>{{ $leg->route_leg }}</td>
              <td>{{ $leg->dpt_airport_id }}</td>
              <td>{{ $leg->arr_airport_id }}</td>
              <td>{{ $leg->distance->local(0).' '.$units['distance'] }}</td>
              <td>{{ DS_ConvertMinutes($leg->flight_time) }}</td>
              <td class="text-center">
                @if(filled($leg->start_date) && filled($leg->end_date))
                  {{ $leg->start_date->startOfDay()->format('l d.M.Y H:i') }} > {{ $leg->end_date->endOfDay()->format('l d.M.Y H:i') }}
                @else
                  <span title="Same with tour">--</span>
                @endif
              </td>
              <td class="text-center">
                @if($leg->subfleets->count() > 0)
                  <span title="@foreach($leg->subfleets as $sf){{ $sf->type.' '.$sf->name }}@if(!$loop->last){{ ', '}}@endif @endforeach">{{ $leg->subfleets->count() }}</span>
                @else
                  <span title="No Subfleets Assigned, Open according to VA Settings">--</span>
                @endif
              </td>
              <td>{{ $leg->notes }}</td>
              <td class="text-right">
                {{ $leg->active ? 'Active' : 'Inactive' }}, {{ $leg->visible ? 'Visible' : 'Not Visible' }}, {!! $leg->owner_id ? 'Owned' : '<span class="text-danger"><b>Not Owned</b></span>' !!}
              </td>
              <td class="text-right">
                <a class="btn btn-sm btn-primary p-0 m-0" href="{{ route('admin.flights.edit', [$leg->id]) }}" target="_blank">Edit Flight</a>
              </td>
            </tr>
          @endforeach
        </table>
      </div>
    @endif
  </div>
  <div class="row text-center" style="margin: 5px;">
    <h4 style="margin: 5px; padding:0px;"><b>Tour Subfleet Management</b></h4>
  </div>
  <div class="row" style="margin-left:5px; margin-right:5px; margin-bottom: 10px;">
    <div class="card border-blue-bottom" style="padding:10px;">
      <div class="row">
        <div class="col-sm-4">
          <label class="pl-1 mb-1" for="tour_id">Select Tour</label>
          <select id="tour_subfleet" class="form-control select2" multiple onchange="checksf()">
            @foreach($alltours->sortBy('tour_name') as $toursf)
              <option value="{{ $toursf->tour_code }}" @if($toursf->id == request('touredit')) selected @endif>{{ $toursf->tour_name }} : {{ $toursf->tour_code }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-sm-4">
          <label class="pl-1 mb-1" for="tour_id">Select SubFleet</label>
          <select id="subfleet" class="form-control select2" multiple onchange="checksf()">
            @foreach($subfleets as $subfleet)
              <option value="{{ $subfleet->id }}" @if(in_array($subfleet->id, $toursfs)) selected @endif>{{ $subfleet->airline->icao }} | {{ $subfleet->name }} : {{ $subfleet->type }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-sm-4">
          <a id="sfadd" href="" class="btn btn-primary pl-1 mb-1" style="margin-top: 25px; visibility: hidden;">Add</a>
          <a id="sfremove" href="" class="btn btn-secondary pl-1 mb-1" style="margin-top: 25px; visibility: hidden;">Remove</a>
        </div>
      </div>
    </div>
  </div>
  <style>
    ::placeholder { color: darkblue !important; opacity: 0.6 !important; }
    :-ms-input-placeholder { color: darkblue !important; }
    ::-ms-input-placeholder { color: darkblue !important; }
  </style>
@endsection

@section('scripts')
  @parent
  <script type="text/javascript">
    // Simple Selection With Dropdown Change
    // Also keep button hidden until a valid selection
    const $oldlink = document.getElementById("edit_link").href;

    function checkselection() {
      if (document.getElementById("tour_selection").value === "0") {
        document.getElementById('edit_link').style.visibility = 'hidden';
      } else {
        document.getElementById('edit_link').style.visibility = 'visible';
      }
      const selected = document.getElementById("tour_selection").value;
      const newlink = "?touredit=".concat(selected);

      document.getElementById("edit_link").href = $oldlink.concat(newlink);
    }
  </script>
  <script type="text/javascript">
    // Simple Selection With Dropdown Change
    // Also keep button hidden until a valid selection
    const $sfaddlink = document.getElementById("sfadd").href;
    const $sfremovelink = document.getElementById("sfremove").href;

    function checksf() {
      const selectedTours = Array.from(document.querySelectorAll('#tour_subfleet option:checked')).map(option => option.value);
      const selectedSubFleets = Array.from(document.querySelectorAll('#subfleet option:checked')).map(option => option.value);

      if (selectedTours.length > 0 && selectedSubFleets.length > 0)
      {
        document.getElementById('sfadd').style.visibility = 'visible';
        document.getElementById('sfremove').style.visibility = 'visible';
      } else {
        document.getElementById('sfadd').style.visibility = 'hidden';
        document.getElementById('sfremove').style.visibility = 'hidden';
      }
      const tcode = selectedTours.join(',');
      const sfid = selectedSubFleets.join(',');
      @if(filled(request('touredit')))
        const addlink = "&act=add&tcode=".concat(tcode,"&sfid=",sfid);
        const removelink = "&act=remove&tcode=".concat(tcode,"&sfid=",sfid);
      @else
        const addlink = "?act=add&tcode=".concat(tcode,"&sfid=",sfid);
        const removelink = "?act=remove&tcode=".concat(tcode,"&sfid=",sfid);
      @endif
      document.getElementById("sfadd").href = $sfaddlink.concat(addlink);
      document.getElementById("sfremove").href = $sfremovelink.concat(removelink);
    }
  </script>
  <script src="{{ public_asset('assets/vendor/ckeditor4/ckeditor.js') }}"></script>
  <script>$(document).ready(function () { CKEDITOR.replace('editor_desc'); });</script>
  <script>$(document).ready(function () { CKEDITOR.replace('editor_rules'); });</script>
@endsection
