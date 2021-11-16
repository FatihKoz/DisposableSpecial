{{-- Current State --}}
<div class="row m-1">
  <div class="col">
    <div class="progress" style="height: 18px;">
      <div
        class="progress-bar @if($maint->curr_state < 50) bg-danger @elseif($maint->curr_state >= 50 && $maint->curr_state < 75) bg-warning @else bg-success @endif"
        role="progressbar"
        style="width: {{ floor($maint->curr_state) }}%"
        aria-valuenow="{{ floor($maint->curr_state) }}" aria-valuemin="0" aria-valuemax="100">
        @lang('DSpecial::common.current_st') %{{ floor($maint->curr_state) }}
      </div>
    </div>
  </div>
</div>
{{-- Scheduled Checks --}}
<div class="card-footer bg-transparent p-1">
  <div class="row row-cols-3 gx-2">
    <div class="col">
      <table class="table table-sm table-borderless table-striped align-middle mb-0">
        <tr>
          <th class="text-center m-0 p-0" colspan="2">A Check</th>
        </tr>
        <tr>
          <td class="m-0 p-0 px-1">Rem. Time</td>
          <td class="text-end m-0 p-0 px-1">@minutestotime(floor($maint->limits->time_a - $maint->time_a))</td>
        </tr>
        <tr>
          <td class="m-0 p-0 px-1">Rem. Cycle</td>
          <td class="text-end m-0 p-0 px-1">{{ floor($maint->limits->cycle_a - $maint->cycle_a) }}</td>
        </tr>
        <tr>
          <td class="m-0 p-0 px-1">Last Check</td>
          <td class="text-end m-0 p-0 px-1">{{ $maint->last_a ?? 'Planned' }}</td>
        </tr>
      </table>
    </div>
    <div class="col">
      <table class="table table-sm table-borderless table-striped align-middle mb-0">
        <tr>
          <th class="text-center m-0 p-0" colspan="2">B Check</th>
        </tr>
        <tr>
          <td class="m-0 p-0 px-1">Rem. Time</td>
          <td class="text-end m-0 p-0 px-1">@minutestotime(floor($maint->limits->time_b - $maint->time_b))</td>
        </tr>
        <tr>
          <td class="m-0 p-0 px-1">Rem. Cycle</td>
          <td class="text-end m-0 p-0 px-1">{{ floor($maint->limits->cycle_b - $maint->cycle_b) }}</td>
        </tr>
        <tr>
          <td class="m-0 p-0 px-1">Last Check</td>
          <td class="text-end m-0 p-0 px-1">{{ $maint->last_b ?? 'Planned' }}</td>
        </tr>
      </table>
    </div>
    <div class="col">
      <table class="table table-sm table-borderless table-striped align-middle mb-0">
        <tr>
          <th class="text-center m-0 p-0" colspan="2">C Check</th>
        </tr>
        <tr>
          <td class="m-0 p-0 px-1">Rem. Time</td>
          <td class="text-end m-0 p-0 px-1">@minutestotime(floor($maint->limits->time_c - $maint->time_c))</td>
        </tr>
        <tr>
          <td class="m-0 p-0 px-1">Rem. Cycle</td>
          <td class="text-end m-0 p-0 px-1">{{ floor($maint->limits->cycle_c - $maint->cycle_c) }}</td>
        </tr>
        <tr>
          <td class="m-0 p-0 px-1">Last Check</td>
          <td class="text-end m-0 p-0 px-1">{{ $maint->last_c ?? 'Planned' }}</td>
        </tr>
      </table>
    </div>
  </div>
</div>
{{-- Last Operational Check --}}
@if($maint->last_note)
  <div class="card-footer bg-transparent p-1">
    <div class="row">
      <div class="col text-center">
        <span>
          @lang('DSpecial::common.last_action'): <b>{{ $maint->last_note }}</b> | 
          @lang('DSpecial::common.completed'): {{ Carbon::parse($maint->last_time)->format('d-M-Y H:i').' UTC' }}
        </span>
      </div>
    </div>
  </div>
@endif
{{-- Ongoing Maintenance --}}
@if($maint->act_start)
  <div class="card-footer bg-transparent p-1">
    <div class="row">
      <div class="col">
        <table class="table table-sm table-borderless table-striped align-middle mb-0">
          <tr>
            <th class="text-center text-danger m-0 p-0" colspan="2">@lang('DSpecial::common.under_maint')</th>
          </tr>
          <tr>
            <td class="m-0 p-0 px-1">@lang('DSpecial::common.current_op')</td>
            <td class="text-end m-0 p-0 px-1">{{ $maint->act_note }}</td>
          </tr>
          <tr>
            <td class="m-0 p-0 px-1">@lang('DSpecial::common.rem_time')</td>
            <td class="text-end m-0 p-0 px-1">{{ Carbon::parse($maint->act_end)->diffForHumans() }}</td>
          </tr>
        </table>
      </div>
    </div>
  </div>
@endif
