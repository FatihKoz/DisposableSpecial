@if($is_visible)
  <div class="card mb-2">
    <div class="card-header p-1">
      <h5 class="m-1">
        @lang('DSpecial::common.mn_assignments')
        <i class="fas fa-hourglass-half float-end"></i>
      </h5>
    </div>
    <div class="card-body text-center p-0 table-responsive">
      <table class="table table-sm table-striped table-borderless mb-0 align-middle text-start text-nowrap">
        <tr>
          @if($hide === false)
            <th>&nbsp;</th>
          @endif
          <th>{{ trans_choice('common.flight', 1) }}</th>
          <th>@lang('airports.departure')</th>
          <th>@lang('airports.arrival')</th>
        </tr>
        @foreach($assignments->sortBy('assignment_order', SORT_NATURAL) as $as)
          @if($as->flight)  
            <tr>
              @if($hide === false)
                <td>
                  @if($as->completed)
                    <i class="fas fa-check-circle text-success"></i>
                  @else
                    <i class="fas fa-exclamation-circle text-danger"></i>
                  @endif
                </td>
              @endif
              <td>
                <a href="{{ route('frontend.flights.show', [$as->flight->id]) }}">
                  {{ optional($as->flight->airline)->code.' '.$as->flight->flight_number }}
                </a>
              </td>
              <td>
                <a href="{{ route('frontend.airports.show', [$as->flight->dpt_airport_id]) }}">
                  {{ optional($as->flight->dpt_airport)->name ?? $as->flight->dpt_airport_id }}
                </a>
              </td>
              <td>
                <a href="{{ route('frontend.airports.show', [$as->flight->arr_airport_id]) }}">
                  {{ optional($as->flight->arr_airport)->name ?? $as->flight->arr_airport_id }}
                </a>
              </td>
            </tr>
          @endif
        @endforeach
      </table>
    </div>
    @if($hide === true)
      <div class="card-footer text-start p-0 px-1 small fw-bold">
        {{ $counts['completed'].'/'.$counts['total'] }}
        <span class="float-end">
          <a href="{{ route('DSpecial.assignments') }}">@lang('DSpecial::common.assignments')</a>
        </span>
      </div>
    @endif
  </div>
@endif
