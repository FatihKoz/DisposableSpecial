<div class="col-lg">
  <div class="card mb-2">
    <div class="card-header p-0">
      <table class="table table-sm table-borderless align-middle py-0 my-0">
        <tr>
          <td class="text-start text-white">
            <h5 class="m-1">
              <a href="{{ route('DSpecial.tour', [$tour->tour_code]) }}">{{ $tour->tour_name }}</a>
            </h5>
          </td>
          <td class="text-end text-white">
            @if($tour->airline)<img class="img-mh40" src="{{ $tour->airline->logo }}" alt="">@endif
          </td>
        </tr>
      </table>
    </div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-sm table-borderless table-striped text-start mb-0">
        <tr>
          <th class="col-2">@lang('DSpecial::tours.ttype')</th>
          <td class="col-6">@if(!$tour->airline) @lang('DSpecial::tours.topen') @else @lang('DSpecial::tours.tairline') @endif</td>
          <th class="col-2 text-end">@lang('DSpecial::tours.tcode')</th>
          <td class="col-2">{{ $tour->tour_code }}</td>
        </tr>
        <tr>
          <th class="col-2">@lang('DSpecial::tours.tdates')</th>
          <td class="col-6">
            {{ $tour->start_date->format('d.M.Y') }} - {{ $tour->end_date->format('d.M.Y') }}
            @if($carbon_now < $tour->start_date)
              <i class="fas fa-info-circle mx-2" title="@lang('DSpecial::tours.iconnoty')" style="color: darkorange;"></i>
            @endif
            @if($carbon_now > $tour->end_date)
              <i class="fas fa-info-circle mx-2" title="@lang('DSpecial::tours.iconend')" style="color: darkred;"></i>
            @endif
          </td>
          <th class="col-2 text-end">@lang('DSpecial::tours.tlegs')</th>
          <td class="col-2">{{ $tour->legs_count }}</td>
        </tr>
        @if($tour->tour_desc)
          <tr>
            <td colspan="4">{!! $tour->tour_desc !!}</td>
          </tr>
        @endif
      </table>
    </div>
  </div>
</div>
