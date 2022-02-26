@if($pilots->count() > 0)
  <table class="table table-sm table-borderless table-striped text-start align-middle mb-0">
    <tr>
      <th class="text-nowrap">Pilot</th>
      @for($x = 1; $x <= $tour->legs_count; $x++)
        <th class="text-center">{{ 'L.'.$x }}</th>
      @endfor
    </tr>
    @foreach($pilots as $pilot)
      <tr>
        <th class="text-nowrap">
          <a href="{{ route('frontend.profile.show', [$pilot->id]) }}">{{ $pilot->ident.' - '.$pilot->name_private }}</a>
        </th>
        @for($y = 1; $y <= $tour->legs_count; $y++)
          <td class="text-center">
            @if(DS_IsTourLegFlown($tour, $tour->legs->firstWhere('route_leg', $y), $pilot->id) === true)
              <i class="fas fa-check-circle text-success" title="Leg {{$y}}"></i>
            @endif
          </td>
        @endfor
      </tr>
    @endforeach
  </table>
@else
  <span class="fw-bold text-danger">Nothing to report</span>
@endif