<div class="col">
    <div class="card mb-2">
      <div class="card-header p-0">
        <h5 class="m-1">
          <a href="{{ route('DSpecial.event', [$event->event_code]) }}">{{ $event->event_name }}</a>
          <i class="fas fa-calendar float-end"></i>
        </h5>
      </div>
      <div class="card-body p-0 table-responsive">
        <table class="table table-sm table-borderless table-striped text-start mb-0">
          <tr>
            <th class="col-2">@lang('DSpecial::tours.ttype')</th>
            <td class="col-6">@lang('DSpecial::tours.topen')</td>
            <th class="col-2 text-end">@lang('DSpecial::tours.tcode')</th>
            <td class="cold-2">{{ $event->event_code }}</td>
          </tr>
          <tr>
            <th class="col-2">@lang('DSpecial::tours.tdates')</th>
            <td class="col-6">
              {{ Carbon::parse($event->start_date)->format('d.M.Y') }} - {{ $event->end_date ? Carbon::parse($event->end_date)->format('d.M.Y') : 'Do not close' }}
              @if(Carbon::now() < Carbon::parse($event->start_date))
                <i class="fas fa-info-circle mx-2" title="@lang('DSpecial::tours.iconnoty')" style="color: darkorange;"></i>
              @endif
              @if(Carbon::now() > Carbon::parse($event->end_date))
                <i class="fas fa-info-circle mx-2" title="@lang('DSpecial::tours.iconend')" style="color: darkred;"></i>
              @endif
            </td>
            <th class="col-2 text-end">@lang('DSpecial::tours.tlegs')</th>
            <td class="col-2">{{ $event->flights_count }}</td>
          </tr>
          @if($event->event_desc)
            <tr>
              <td colspan="4">{!! $event->event_desc !!}</td>
            </tr>
          @endif
        </table>
      </div>
    </div>
  </div>
  