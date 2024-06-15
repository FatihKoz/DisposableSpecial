@if($pilots->count() > 0)
  <table class="table table-sm table-borderless table-striped text-start text-nowrap align-middle mb-0">
    <tr>
      <th class="text-nowrap">Pilot</th>
      @for($x = 1; $x <= $tour->legs_count; $x++)
        <th class="text-center">{{ 'L.'.$x }}</th>
      @endfor
    </tr>
    @foreach($pilots as $pilot)
      <tr>
        <th class="text-nowrap">
          <a href="{{ route('frontend.profile.show', [$pilot->id]) }}">
            @if(Theme::getSetting('roster_ident')) {{ $pilot->ident.' - ' }} @endif
            {{ $pilot->name_private }}
          </a>
          @if($tour_report[$pilot->id]['order'] === false)
            <span class="float-end me-1"><i class="fas fa-tasks text-danger" title="Flown Order: {{ $tour_report[$pilot->id]['flown'] }}"></i></span>
          @endif
        </th>
        @for($y = 1; $y <= $tour->legs_count; $y++)
          <td class="text-center">
            @if(isset($tour_report[$pilot->id][$y]) && $tour_report[$pilot->id][$y] === true)
              <i class="fas fa-check-circle @if($tour_report[$pilot->id]['order'] === true) text-success @else text-danger @endif" title="Leg {{$y}}"></i>
            @endif
          </td>
        @endfor
      </tr>
    @endforeach
  </table>
@else
  <span class="fw-bold text-danger">Nothing to report</span>
@endif