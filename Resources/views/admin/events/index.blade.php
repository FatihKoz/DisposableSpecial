@extends('admin.app')
@section('title', 'Disposable Events')

@section('content')
  <div class="card border-blue-bottom" style="margin-left:5px; margin-right:5px; margin-bottom:5px;">
    <div class="content">
      <p>Only Event Details are defined (or edited) here, legs must be inserted from Admin/Flights or better should be
        csv imported to ease the process.</p>
      <p>Event Code defined here <b>MUST MATCH</b> the flight/route code and flight/route legs must be defined for tours
        to work properly.</p>
      <p>Events will be displayed at Event page according to users and start/end dates provided here.</p>
      <p>&nbsp;</p>
      <p><a href="https://github.com/FatihKoz" target="_blank">&copy; B.Fatih KOZ</a></p>
    </div>
  </div>

  <div class="row text-center" style="margin: 5px;">
    <h4 style="margin: 5px; padding:0px;"><b>Event Management</b></h4>
  </div>

  <div class="row" style="margin-left:5px; margin-right:5px;">
    <div class="card border-blue-bottom" style="padding:10px;">
      {{ Form::open(array('route' => 'DSpecial.event_store', 'method' => 'post')) }}
      <input type="hidden" name="id" value="{{ $event->id ?? '' }}">
      <div class="row" style="margin-bottom: 10px;">
        <div class="col-sm-6">
          <label class="pl-1 mb-1" for="event_id">Select Pre-Recorded Events for Editing</label>
          <select id="event_selection" class="form-control select2" onchange="checkselection()">
            <option value="0">Please Select An Event</option>
            @foreach($allevents->sortBy('event_name') as $eventlist)
              <option value="{{ $eventlist->id }}"
                      @if($event &&  $event->id == $eventlist->id) selected @endif>{{ $eventlist->event_name }}
                : {{ $eventlist->event_code }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-sm-4 text-left align-middle"><br>
          <a id="edit_link" style="visibility: hidden" href="{{ route('DSpecial.event_admin') }}"
             class="btn btn-primary pl-1 mb-1">Load Selected Event For Edit</a>
        </div>
      </div>

      <div class="row" style="margin-bottom: 10px;">
        <div class="col-sm-6">
          <label class="pl-1 mb-1" for="event_name">Event Name *</label>
          <input name="event_name" type="text" class="form-control" placeholder="Mandatory" maxlength="150"
                 value="{{ $event->event_name ?? '' }}">
        </div>
        <div class="col-sm-2">
          <label class="pl-1 mb-1" for="tour_code">Event Code *</label>
          <input name="event_code" type="text" class="form-control" placeholder="Mandatory" maxlength="5"
                 value="{{ $event->event_code ?? '' }}">
        </div>
        <div class="col-sm-2">
          <label class="pl-1 mb-1" for="start_date">Start Date *</label>
          <input name="start_date" type="date" class="form-control" placeholder="2021-03-15"
                 value="{{ $event->start_date ?? '' }}">
        </div>
        <div class="col-sm-2">
          <label class="pl-1 mb-1" for="end_date">End Date (Optional)</label>
          <input name="end_date" type="date" class="form-control" placeholder="2021-06-30"
                 value="{{ $event->end_date ?? '' }}">
        </div>
      </div>

      <div class="row" style="margin-bottom: 10px;">
        <div class="col-sm-8">
          <label class="pl-1 mb-1" for="tour_desc">Event Description (Optional)</label>
          <textarea id="editor_desc" name="event_desc" class="editor_desc">{!! $event->event_desc ?? '' !!}</textarea>
        </div>
      </div>

      <div class="row" style="margin-bottom: 10px;">
        <div class="col-sm-2 text-left">
          <input type="hidden" name="public" value="0">
          <label class="pl-1 mb-1" for="public">Public <input name="public" type="checkbox"
                                                              @if($event && $event->public == 1) checked="true"
                                                              @endif class="form-control" value="1"></label>
        </div>
        <div class="col-sm-10 text-right">
          <button class="btn btn-primary pl-1 mb-1" type="submit">@if($event && $event->id) Update @else
              Save @endif</button>
        </div>
      </div>
      {{ Form::close() }}
    </div>
  </div>

  <div class="row text-center" style="margin: 5px;">
    <h4 style="margin: 5px; padding:0px;"><b></b></h4>
  </div>
  @if(!is_null($event))
    <div class="card border-blue-bottom">
      <div class="content">
        <div class="header">
          <h3>Event Pilots Management</h3>
          @component('admin.components.info')
            These are the users assigned to this event
          @endcomponent
        </div>
        <div class="row">
          @include('DSpecial::admin.events.users')
        </div>
      </div>
    </div>
  @endif

  <style>
    ::placeholder {
      color: darkblue !important;
      opacity: 0.6 !important;
    }

    :-ms-input-placeholder {
      color: darkblue !important;
    }

    ::-ms-input-placeholder {
      color: darkblue !important;
    }
  </style>
@endsection
@section('scripts')
  @parent
  <script type="text/javascript">
    // Simple Selection With Dropdown Change
    // Also keep button hidden until a valid selection
    const $oldlink = document.getElementById("edit_link").href;

    function checkselection() {
      if (document.getElementById("event_selection").value === "0") {
        document.getElementById('edit_link').style.visibility = 'hidden';
      } else {
        document.getElementById('edit_link').style.visibility = 'visible';
      }
      const selected = document.getElementById("event_selection").value;
      const newlink = "?eventedit=".concat(selected);

      document.getElementById("edit_link").href = $oldlink.concat(newlink);
    }

    $(document).ready(function () {
      initActions();
      $(document).on('submit', 'form.pjax_form', function (event) {
        event.preventDefault();
        $.pjax.submit(event, '#event_users_wrapper', {push: false});
      });
    });
  </script>
  <script src="{{ public_asset('assets/vendor/ckeditor4/ckeditor.js') }}"></script>
  <script>$(document).ready(function () {
      CKEDITOR.replace('editor_desc');
    });</script>
@endsection
