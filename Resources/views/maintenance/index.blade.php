@extends('app')
@section('title', 'Fleet Maintenance')

@section('content')
  @if(!$maintenance->count() && !$activemaint->count())
    <div class="alert alert-info p-1 fw-bold">Congratulations... All fleet members are in good shape.</div>
  @else
    <div class="row row-cols-1">
      {{-- Active Jobs --}}
      @if(filled($activemaint))
        <div class="col-8">
          <div class="card mb-2">
            <div class="card-header p-1">
              <h5 class="m-1">
                Maintenance In Progress
                <i class="fas fa-wrench float-end"></i>
              </h5>
            </div>
            <div class="card-body p-0 text-start">
              <table class="table table-sm table-borderless table-striped text-center align-middle mb-0">
                <tr>
                  <th class="text-start">Aircraft</th>
                  <th class="text-start">Location</th>
                  <th>Expected State</th>
                  <th>Current Operation</th>
                  <th>Started Time</th>
                  <th>Scheduled End</th>
                  <th class="text-end">Time Remaining</th>
                  @if($staff_check)
                    <th class="text-end">Actions</th>
                  @endif
                </tr>
                @foreach($activemaint as $active)
                  <form class="form" method="post" action="{{ route('DSpecial.maint_finish') }}">
                    @csrf
                    <tr>
                      <td class="text-start">
                        @if($DBasic) <a href="{{ route('DBasic.aircraft', [optional($active->aircraft)->registration ?? '']) }}"> @endif
                          {{ optional($active->aircraft)->ident }}
                          @if($active->aircraft && $active->aircraft->registration != $active->aircraft->name) {{ "'".$active->aircraft->name."'" }} @endif
                        @if($DBasic) </a> @endif
                      </td>
                      <td class="text-start">{{ optional($active->aircraft)->airport_id }}</td>
                      <td>{{ $active->curr_state.' %' }}</td>
                      <td>{{ $active->act_note }}</td>
                      <td>{{ $active->act_start->format('d.M.Y H:i') }}</td>
                      <td>{{ $active->act_end->format('d.M.Y H:i') }}</td>
                      <td class="text-end">{{ $active->act_end->diffForHumans() }}</td>
                      @if($staff_check)
                        <td class="text-end">
                          <input type="hidden" name="id" value="{{ $active->id }}" />
                          <input type="hidden" name="act_note" value="{{ $active->act_note }}" />
                          <button class="btn btn-sm btn-success m-0 px-1 py-0 me-1" type="submit">Finish Maintenance</button>
                        </td>
                      @endif
                    </tr>
                  </form>
                @endforeach
              </table>
            </div>
            <div class="card-footer p-1 small fw-bold text-end">All times are in UTC</div>
          </div>
        </div>
      @endif
      {{-- Fleet Status --}}             
      <div class="col">
        <div class="card mb-2">
          <div class="card-header p-1">
            <h5 class="m-1">
              Fleet Maintenance Status
              <i class="fas fa-tools float-end"></i>
            </h5>
          </div>
          <div class="card-body p-0 text-left">
            <table class="table table-sm table-borderless table-striped text-center align-middle mb-0">
              <tr>
                <th class="text-start">Aircraft</th>
                <th class="text-start">Location</th>
                <th>Curr. State</th>
                <th>Last Flight</th>
                <th>Last Check Type</th>
                <th>Last Check Time</th>
                <th colspan="2">A Check (Rem.)</th>
                <th colspan="2">B Check (Rem.)</th>
                <th colspan="2">C Check (Rem.)</th>
                @if($staff_check)
                  <th>Actions</th>
                @endif
              </tr>
              @foreach($maintenance as $maint)
                <tr>
                  <td class="text-start">
                    @if($DBasic) <a href="{{ route('DBasic.aircraft', [optional($maint->aircraft)->registration ?? '']) }}"> @endif
                      {{ optional($maint->aircraft)->ident }}
                    @if($DBasic) </a> @endif
                  </td>
                  <td class="text-start">{{ optional($maint->aircraft)->airport_id }}</td>
                  <td>{{ $maint->curr_state.' %' }}</td>
                  <td>
                    @if($maint->aircraft)
                      {{ optional($maint->aircraft->landing_time)->format('d.M.Y H:i') }}
                    @endif
                  </td>
                  <td>{{ $maint->last_note }}</td>
                  <td>
                    @if(filled($maint->last_time))
                      {{ $maint->last_time->format('d.M.Y H:i') }}
                    @endif
                  </td>
                  {{-- A Check Remaining --}}
                  <td>{{ ($maint->rem_ca).' Cycles' }}</td>
                  <td>
                    @if($maint->rem_ta < 0)<span class="text-danger fw-bold">@endif
                    {{ DS_ConvertMinutes($maint->rem_ta, '%2dh') }}
                    @if($maint->rem_ta < 0)</span>@endif
                  </td>
                  {{-- B Check Remaining --}}
                  <td>{{ ($maint->rem_cb).' Cycles' }}</td>
                  <td>
                    @if($maint->rem_tb < 0)<span class="text-danger fw-bold">@endif
                    {{ DS_ConvertMinutes($maint->rem_tb, '%2dh') }}
                    @if($maint->rem_tb < 0)</span>@endif
                  </td>
                  {{-- C Check Remaining --}}
                  <td>{{ ($maint->rem_cc).' Cycles' }}</td>
                  <td>
                    @if($maint->rem_tc < 0)<span class="text-danger fw-bold">@endif
                      {{ DS_ConvertMinutes($maint->rem_tc, '%2dh') }}
                    @if($maint->rem_tc < 0)</span>@endif
                  </td>
                  {{-- Action Buttons --}}
                  @if($staff_check)
                    <td>
                      @if(optional($maint->aircraft)->state === 0)
                        <form class="form" method="post" action="{{ route('DSpecial.maint_finish') }}">
                          @csrf
                          <input type="hidden" name="id" value="{{ $maint->id }}" />
                          <input type="hidden" name="ops" value="manual" />
                          @if($maint->rem_tc < 600 || $maint->rem_cc < 3)
                            <input type="hidden" name="act_note" value="C Check" />
                            <button class="btn btn-sm btn-danger m-0 px-1 py-0" type="submit">Perform C Check</button>
                          @elseif($maint->rem_tb < 600 || $maint->rem_cb < 3)
                            <input type="hidden" name="act_note" value="B Check" />
                            <button class="btn btn-sm btn-warning m-0 px-1 py-0" type="submit">Perform B Check</button>
                          @elseif ($maint->rem_ta < 600 || $maint->rem_ca < 3)
                            <input type="hidden" name="act_note" value="A Check" />
                            <button class="btn btn-sm btn-warning m-0 px-1 py-0" type="submit">Perform A Check</button>
                          @elseif ($maint->curr_state < 75)
                            <input type="hidden" name="act_note" value="Line Check" />
                            <button class="btn btn-sm btn-secondary m-0 px-1 py-0" type="submit">Perform Line Check</button>
                          @else
                            <button class="btn btn-sm btn-success m-0 px-1 py-0" type="button" disabled>Aircraft Servicable</button>
                          @endif
                        </form>
                      @else
                        <button class="btn btn-sm btn-primary m-0 px-1 py-0" type="button" disabled>Aircraft In Flight</button>
                      @endif
                    </td>
                  @endif
                </tr>
              @endforeach
            </table>
          </div>
        </div>
      </div>
    </div>
  @endif

  {{ $maintenance->links('pagination.default') }}
@endsection
