@extends('app_nonav')
@section('title', trans_choice('common.pilot', 2))

@section('content')
  <div class="row">
    <div class="col">
      <div class="card mb-2">
        <div class="card-header p-1">
          <h5 class="m-1 p-0">
            @lang('TurkSim::common.allpilots')
            <i class="fas fa-users float-right"></i>
          </h5>
        </div>
        <div class="card-body p-0">
          <table class="table table-sm table-striped table-borderless mb-0 text-center">
            <thead>
              <th class="text-left"></th>
              @if(Theme::getSetting('roster_ident'))
                <th class="text-left">@lang('flights.callsign')</th>
              @endif
              <th class="text-left">@lang('common.name')</th>
              <th>@lang('common.country')</th>
              <th>@lang('airports.home')</th>
              <th>@lang('common.airline')</th>
              <th>@lang('airports.current')</th>
              <th>{{ trans_choice('common.flight', 2) }}</th>
              <th>{{ trans_choice('common.hour', 2) }}</th>
              @if(setting('pilots.allow_transfer_hours') === true)
                <th>@lang('disposable.transferhours')</th>
              @endif
              @if(Theme::getSetting('total_hours'))
                <th>@lang('disposable.total')</th>
              @endif
              <th>@lang('disposable.rank')</th>
              <th>@lang('disposable.awards')</th>
              @if(Theme::getSetting('roster_ivao'))
                <th>IVAO</th>
              @endif
              @if(Theme::getSetting('roster_vatsim'))
                <th>VATSIM</th>
              @endif
              @if(Theme::getSetting('roster_poscon'))
                <th>POSCON</th>
              @endif
              <th>@lang('common.state')</th>
            </thead>
            <tbody>
              @foreach($allpilots as $user)
                <tr>
                  <td class="text-left align-middle">
                    @if ($user->avatar == null)
                      <img class="rounded img-h50 border border-dark" src="{{ public_asset('/disposable/nophoto.jpg') }}"/>
                    @else
                      <img class="rounded img-h50 border border-dark" src="{{ $user->avatar->url }}">
                    @endif
                  </td>
                  @if(Theme::getSetting('roster_ident'))
                    <td class="text-left align-middle"><b>{{$user->ident}}</b></td>
                  @endif
                  <td class="text-left align-middle"><a href="{{ route('frontend.profile.show', [$user->id]) }}"><b>{{ $user->name_private }}</b></a></td>
          
                  <td class="align-middle">
                    @if(filled($user->country))
                      <span class="flag-icon flag-icon-{{ $user->country }}" title="{{ $country->alpha2($user->country)['name'] }}" style="font-size: 1.5rem;"></span>
                    @endif
                  </td>
                  <td class="align-middle">
                    @if($user->home_airport)
                      @if(Dispo_Modules('DisposableHubs'))
                        <a href="{{route('DisposableHubs.hshow', [$user->home_airport->icao])}}" title="{{ $user->home_airport->name ?? '' }}">
                      @else
                        <a href="{{route('frontend.airports.show', [$user->home_airport->icao])}}" title="{{ $user->home_airport->name ?? '' }}">
                      @endif
                      {{ $user->home_airport->icao }}</a>
                    @endif
                  </td>
                  <td class="align-middle">
                    @if(Dispo_Modules('DisposableAirlines'))<a href="{{ route('DisposableAirlines.ashow', [$user->airline->icao]) }}">@endif
                    @if(filled($user->airline->logo))
                      <img class="img-mh40" src="{{ $user->airline->logo }}" title="{{ $user->airline->name }}">
                    @else
                      {{ $user->airline->name }}
                    @endif
                    @if(Dispo_Modules('DisposableAirlines'))</a>@endif
                  </td>
                  <td class="align-middle">
                    @if($user->current_airport)
                      <a href="{{route('frontend.airports.show', [$user->current_airport->icao])}}" title="{{ $user->current_airport->name ?? '' }}">{{ $user->current_airport->icao }}</a>
                    @endif
                  </td>
                  <td class="align-middle">{{ $user->flights }}</td>
                  <td class="align-middle">@minutestotime($user->flight_time)</td>
                  @if(setting('pilots.allow_transfer_hours') === true)
                    <td class="align-middle">@minutestohours($user->transfer_time)h</td>
                  @endif
                  @if(Theme::getSetting('total_hours'))
                    <td class="align-middle">@minutestotime($user->flight_time + $user->transfer_time)</td>
                  @endif
                  <td class="align-middle">
                    @if(filled($user->rank->image_url))
                      <img class="rounded img-mh40" src="{{ $user->rank->image_url }}" title="{{ $user->rank->name }}">
                    @else
                      {{ $user->rank->name }}
                    @endif
                  </td>
                  <td class="align-middle">
                    @if($user->awards->count() > 0)
                      <i class="fas fa-trophy fa-lg" style="color: darkgreen;" title="{{ $user->awards->count() }} @lang('disposable.awards')"></i>
                    @endif
                  </td>
                  @if(Theme::getSetting('roster_ivao'))
                    <td class="align-middle">
                      @foreach($user->fields->whereNotNull('value') as $field)
                        @if($field->field->name === 'IVAO')
                          <a href='https://www.ivao.aero/Member.aspx?ID={{ $field->value }}' target='_blank'><b>{{ $field->value }}</b></a>
                        @endif
                      @endforeach
                    </td>
                  @endif
                  @if(Theme::getSetting('roster_vatsim'))
                    <td class="align-middle">
                      @foreach($user->fields->whereNotNull('value') as $field)
                        @if($field->field->name === 'VATSIM')
                          <a href='https://stats.vatsim.net/search_id.php?id={{ $field->value }}' target='_blank'><b>{{ $field->value }}</b></a>
                        @endif
                      @endforeach
                    </td>
                  @endif
                  @if(Theme::getSetting('roster_poscon'))
                    <td class="align-middle">
                      @foreach($user->fields->whereNotNull('value') as $field)
                        @if($field->field->name === 'POSCON')
                          <b>{{ $field->value }}</b>
                        @endif
                      @endforeach
                    </td>
                  @endif
                  <td class="align-middle">{!! Dispo_UserStateBadge($user->state) !!}</td>
                </tr>
              @endforeach
            </tbody>
          </table>          
        </div>
        <div class="card-footer p-1 text-right">@lang('TurkSim::common.totpilots'): {{ $allpilots->total() }}</div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col text-center">
      {{ $allpilots->links('pagination.default') }}
    </div>
  </div>
@endsection
