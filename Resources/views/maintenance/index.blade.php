@extends('app')
@section('title', 'Fleet Maintenance')

@section('content')
  @if(!$maintenance->count())
    <div class="alert alert-info p-1 fw-bold">Congratulations... All fleet members are in good shape.</div>
  @else
    <div class="row">
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
                <th>Curr. State</th>
                <th>Last Flight</th>
                <th>Last Check Type</th>
                <th>Last Check Time</th>
                <th>A Check</th>
                <th>B Check</th>
                <th>C Check</th>
                <th>Actions</th>
              </tr>
              @foreach($maintenance as $maint)
                <tr>
                  <td class="text-start">
                    @if($DBasic) <a href="{{ route('DBasic.aircraft', [optional($maint->aircraft)->registration ?? '']) }}"> @endif
                      {{ optional($maint->aircraft)->ident }}</td>
                    @if($DBasic) </a> @endif
                  <td>{{ $maint->curr_state.' %' }}</td>
                  <td>
                    @if($maint->aircraft)
                      {{ optional($maint->aircraft->landing_time)->format('d.M.Y H:i') }}
                    @endif
                  </td>
                  <td>{{ $maint->last_note }}</td>
                  <td>{{ $maint->last_time->format('d.M.Y H:i') }}</td>
                  <td>
                    {{ ($maint->limits->cycle_a - $maint->cycle_a).' Cycles, '.DS_ConvertMinutes(($maint->limits->time_a -$maint->time_a), '%2dh %2dm') }}
                  </td>
                  <td>
                    {{ ($maint->limits->cycle_b - $maint->cycle_b).' Cycles, '.DS_ConvertMinutes(($maint->limits->time_b -$maint->time_b), '%2dh %2dm') }}
                  </td>
                  <td>
                    {{ ($maint->limits->cycle_c - $maint->cycle_c).' Cycles, '.DS_ConvertMinutes(($maint->limits->time_c -$maint->time_c), '%2dh %2dm') }}
                  </td>
                  <td></td>
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
