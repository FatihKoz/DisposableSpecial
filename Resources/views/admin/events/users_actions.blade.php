<div class="col-sm-6">
  <select id="method" class="form-control select2" onchange="checkmethod()">
    <option value="0">Please Select A Method</option>
    <option value="airlines">Airlines</option>
    <option value="hubs">Hubs</option>
    <option value="ranks">Ranks</option>
    <option value="random">Random number</option>
    <option value="users">Pilots list</option>
  </select>
</div>

<div class="col-sm-6 text-right" id="event_airlines_container">
  {{ Form::open(['url' => url('/admin/devent_admin/'.$event->id.'/users'), 'method' => 'post', 'class' => 'pjax_form form-inline col-12']) }}
  {{ Form::hidden('method', 'airlines') }}
  <select name="airline_id" id="event_airlines" class="form-control select2">
    <option value="0">Please Select An Airline</option>
    @foreach($airlines as $airline)
      <option value="{{ $airline->id }}">{{ $airline->name }}</option>
    @endforeach
  </select>
  {{ Form::button('<i class="fa fa-plus"></i> Add', ['type' => 'submit', 'class' => 'btn btn-success btn-small']) }}
  {{ Form::close() }}
</div>

<div class="col-sm-6 text-right" id="event_hubs_container">
  {{ Form::open(['url' => url('/admin/devent_admin/'.$event->id.'/users'), 'method' => 'post', 'class' => 'pjax_form form-inline col-12']) }}
  {{ Form::hidden('method', 'hubs') }}
  <select name="hub_id" id="event_hubs" class="form-control select2">
    <option value="0">Please Select A Hub</option>
    @foreach($hubs as $hub)
      <option value="{{ $hub->id }}">{{ $hub->full_name ?? $hub->icao }}</option>
    @endforeach
  </select>
  {{ Form::button('<i class="fa fa-plus"></i> Add', ['type' => 'submit', 'class' => 'btn btn-success btn-small']) }}
  {{ Form::close() }}
</div>

<div class="col-sm-6 text-right" id="event_ranks_container">
  {{ Form::open(['url' => url('/admin/devent_admin/'.$event->id.'/users'), 'method' => 'post', 'class' => 'pjax_form form-inline col-12']) }}
  {{ Form::hidden('method', 'ranks') }}
  <select name="rank_id" id="event_ranks" class="form-control select2">
    <option value="0">Please Select A Rank</option>
    @foreach($ranks as $rank)
      <option value="{{ $rank->id }}">{{ $rank->name }}</option>
    @endforeach
  </select>
  {{ Form::button('<i class="fa fa-plus"></i> Add', ['type' => 'submit', 'class' => 'btn btn-success btn-small']) }}
  {{ Form::close() }}
</div>

<div class="col-sm-6 text-right" id="event_random_container">
  {{ Form::open(['url' => url('/admin/devent_admin/'.$event->id.'/users'), 'method' => 'post', 'class' => 'pjax_form form-inline col-12']) }}
  {{ Form::hidden('method', 'random') }}
  <input name="random_id" id="event_random" type="number" class="form-control" placeholder="Please enter a number" min="0" max="{{ \App\Models\User::where('state', UserState::ACTIVE)->count() }}" value="0">
  {{ Form::button('<i class="fa fa-plus"></i> Add', ['type' => 'submit', 'class' => 'btn btn-success btn-small']) }}
  {{ Form::close() }}
</div>

<div class="col-sm-6 text-right" id="event_users_container">
  {{ Form::open(['url' => url('/admin/devent_admin/'.$event->id.'/users'), 'method' => 'post', 'class' => 'pjax_form form-inline col-12']) }}
  {{ Form::hidden('method', 'users') }}
  <select name="user_id" id="event_users" class="form-control select2">
    <option value="0">Please Select A Pilot</option>
    @foreach($users as $user)
      <option value="{{ $user->id }}">{{ $user->airline->icao . str_pad($user->pilot_id, setting('pilots.id_length'), '0', STR_PAD_LEFT) .' - '. $user->name }}</option>
    @endforeach
  </select>
  {{ Form::button('<i class="fa fa-plus"></i> Add', ['type' => 'submit', 'class' => 'btn btn-success btn-small']) }}
  {{ Form::close() }}
</div>
<script type="text/javascript">

  function initActions() {
    $.when($('.select2').select2({width: 'resolve'})).done(function () {
      document.getElementById("event_airlines_container").style.display = 'none';
      document.getElementById("event_hubs_container").style.display = 'none';
      document.getElementById("event_ranks_container").style.display = 'none';
      document.getElementById("event_random_container").style.display = 'none';
      document.getElementById("event_users_container").style.display = 'none';
    });
    checkmethod()
  }

  function checkmethod() {
    document.getElementById("event_airlines_container").style.display = 'none';
    document.getElementById("event_hubs_container").style.display = 'none';
    document.getElementById("event_ranks_container").style.display = 'none';
    document.getElementById("event_random_container").style.display = 'none';
    document.getElementById("event_users_container").style.display = 'none';

    if (document.getElementById("method").value != "0") {
      if (document.getElementById("method").value == "airlines") {
        document.getElementById("event_airlines_container").style.display = 'block';
      } else if (document.getElementById("method").value == "hubs") {
        document.getElementById("event_hubs_container").style.display = 'block';
      } else if (document.getElementById("method").value == "ranks") {
        document.getElementById("event_ranks_container").style.display = 'block';
      } else if (document.getElementById("method").value == "random") {
        document.getElementById("event_random_container").style.display = 'block';
      } else if (document.getElementById("method").value == "users") {
        document.getElementById("event_users_container").style.display = 'block';
      }
    }
  }
  initActions();
</script>
