@extends('admin.app')
@section('title', 'Disposable Maintenance')

@section('content')
  <div class="card border-blue-bottom" style="margin-left:5px; margin-right:5px; margin-bottom:5px;">
    <div class="content">
      <p>Ongoing maintenance operations can be finished here, also fleet status is listed</p>
      <p>&nbsp;</p>
      <p><a href="https://github.com/FatihKoz" target="_blank">&copy; B.Fatih KOZ</a></p>
    </div>
  </div>

  @if($activemaint->count())
    <div class="row text-center" style="margin:5px;"><h4 style="margin: 5px; padding:0px;"><b>Ongoing Maintenance</b></h4></div>

    <div class="row" style="margin-left:5px; margin-right:5px;">
      <div class="card border-blue-bottom" style="padding:10px;">
        <table class="table table-sm table-striped text-left mt-0 mb-0">
          <tr>
            <th>Registration / Name</th>
            <th>Aircraft State</th>
            <th>Current Operation</th>
            <th>Started Time</th>
            <th>Scheduled End</th>
            <th class="text-right">Actions&nbsp;&nbsp;</th>
          </tr>
          @foreach($activemaint as $active)
          {{ Form::open(['route' => 'DSpecial.maint_finish', 'method' => 'post']) }}
            <tr class="m-0 p-0">
              <td class="m-0 p-0 align-middle">
                {{ optional($active->aircraft)->ident }}
                @if($active->aircraft && $active->aircraft->registration != $active->aircraft->name) {{ "'".$active->aircraft->name."'" }} @endif
              </td>
              <td>
                {{ '%'.$active->curr_state }}
              </td>
              <td class="m-0 p-0 align-middle">
                {{ $active->act_note }}
              </td>
              <td class="m-0 p-0 align-middle">
                {{ $active->act_start }}
              </td>
              <td class="m-0 p-0 align-middle">
                {{ $active->act_end }}
              </td>
              <td class="text-right m-0 p-0 align-middle">
                <input type="hidden" name="id" value="{{ $active->id }}">
                <input type="hidden" name="act_note" value="{{ $active->act_note }}">
                <button class="btn btn-sm btn-success m-0" type="submit">Finish Maintenance</button>
              </td>
            </tr>
          {{ Form::close() }}
          @endforeach
        </table>
      </div>
    </div>
  @endif

  <div class="row text-center" style="margin:5px;"><h4 style="margin: 5px; padding:0px;"><b>Fleet Maintenance Status</b></h4></div>

  <div class="row" style="margin-left:5px; margin-right:5px;">
    <div class="card border-blue-bottom" style="padding:10px;">
      <table class="table table-sm table-striped border-0 text-left mt-0 mb-0">
        <tr>
          <th>Registration / Name</th>
          <th>Curr. State</th>
          <th>A Check (Act / Lmt)</th>
          <th>B Check (Act / Lmt)</th>
          <th>C Check (Act / Lmt)</th>
          <th>Last Check</th>
          <th class="text-right">Actions&nbsp;&nbsp;</th>
        </tr>
        @foreach($maintenance as $maint)
          <tr class="m-0 p-0">
            <td class="m-0 p-0">
              {{ optional($maint->aircraft)->ident }}
              @if($maint->aircraft && $maint->aircraft->registration != $maint->aircraft->name) {{ "'".$maint->aircraft->name."'" }} @endif
            </td>
            <td class="m-0 p-0">{{ '%'.$maint->curr_state }}</td>
            <td class="m-0 p-0">
              &bull;H: @minutestotime($maint->time_a) / @minutestotime($maint->limits->time_a)
              <br>
              &bull;C: {{ $maint->cycle_a.' / '.$maint->limits->cycle_a }}
            </td>
            <td class="m-0 p-0">
              &bull;H: @minutestotime($maint->time_b) / @minutestotime($maint->limits->time_b)
              <br>
              &bull;C: {{ $maint->cycle_b.' / '.$maint->limits->cycle_b }}
            </td>
            <td class="m-0 p-0">
              &bull;H: @minutestotime($maint->time_c) / @minutestotime($maint->limits->time_c)
              <br>
              &bull;C: {{ $maint->cycle_c.' / '.$maint->limits->cycle_c }}
            </td>
            <td class="m-0 p-0">
              {{ $maint->last_note }}
              <br>
              {{ $maint->last_time }}
            </td>
            <td class="m-0 p-0 text-right">
              {{ Form::open(['route' => 'DSpecial.maint_finish', 'method' => 'post']) }}
                <input type="hidden" name="id" value="{{ $maint->id }}">
                <input type="hidden" name="ops" value="manual">
                @if ($maint->limits->time_c - $maint->time_c < 100 || $maint->limits->cycle_c - $maint->cycle_c < 15)
                  <input type="hidden" name="act_note" value="C Check">
                  <button class="btn btn-sm btn-danger m-0" type="submit">Perform C Check</button>
                @elseif ($maint->limits->time_b - $maint->time_b < 50 || $maint->limits->cycle_b - $maint->cycle_b < 10)
                  <input type="hidden" name="act_note" value="B Check">
                  <button class="btn btn-sm btn-warning m-0" type="submit">Perform B Check</button>
                @elseif ($maint->limits->time_a - $maint->time_a < 25 || $maint->limits->cycle_a - $maint->cycle_a < 5)
                  <input type="hidden" name="act_note" value="A Check">
                  <button class="btn btn-sm btn-secondary m-0" type="submit">Perform A Check</button>
                @elseif ($maint->curr_state < 75)
                  <input type="hidden" name="act_note" value="Line Check">
                  <button class="btn btn-sm btn-primary m-0" type="submit">Perform Line Check</button>
                @endif
              {{ Form::close() }}
            </td>
          </tr>
        @endforeach
      </table>
    </div>
  </div>

  @if($maintenance->hasPages())
    <div class="row" style="margin-left:5px; margin-right:5px;">
      <div class="col-sm-12 text-center">
        {{ $maintenance->links('pagination.default') }}
      </div>
    </div>
  @endif

@endsection
