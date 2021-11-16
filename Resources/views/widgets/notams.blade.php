@if($notams->count() > 0)
  <div class="card mb-2">
    <div class="card-header p-1">
      <h5 class="m-1">
        NOTAMs
        @if($notams->count() > 1)
          <i class="fas fa-scroll float-end" title="Show More" data-toggle="collapse" data-target="#notams" aria-expanded="false" aria-controls="notams"></i>
        @else
          <i class="fas fa-clipboard float-end"></i>
        @endif
      </h5>
    </div>
    <div class="card-body text-center p-1">
      @if($notams->count() > 0)
        @foreach($notams as $notam)
          @if($loop->iteration === 2)
            <div class="collapse" id="notams">
          @endif
            @if(!$loop->first) 
              <hr class="m-0 p-0 my-1"/>
            @endif
              <table class="table table-sm table-borderless text-start mb-0">
                <tr>
                  <th class="m-0 p-0" colspan="2">
                    {{ $notam->ident }}
                    @if(filled($notam->ref_airline))
                      {{ ' | '.optional($notam->airline)->name }}
                    @endif
                  </th>
                </tr>
                <tr class="m-0 p-0">
                  <th class="m-0 p-0" style="width: 10px;">A)</th>
                  <td class="m-0 p-0">{{ $notam->ref_airport ?? 'NIL'}}</td>
                </tr>
                <tr class="m-0 p-0">
                  <th class="m-0 p-0">B)</th>
                  <td class="m-0 p-0">{{ $notam->effectivefrom }}</td>
                </tr>
                <tr class="m-0 p-0">
                  <th class="m-0 p-0">C)</th>
                  <td class="m-0 p-0">{{ $notam->effectiveuntil }}</td>
                </tr>
                <tr class="m-0 p-0">
                  <th class="m-0 p-0">E)</th>
                  <td class="m-0 p-0">{!! str_replace($remove, '', $notam->body) !!}</td>
                </tr>
              </table>
          @if($loop->iteration > 1 && $loop->last)
            </div>
          @endif
        @endforeach
      @else
        <span class="text-danger fw-bold">@lang('DSpecial::common.no_notams')</span>
      @endif
    </div>
    <div class="card-footer p-0 px-1 small text-center">
      <span class="float-start">
        <a href="{{ route('DSpecial.notams') }}">All NOTAMs</a>
      </span>
      <span class="float-end fw-bold">
        @lang('DSpecial::common.total') {{ $notams->count() }}
      </span>
    </div>
  </div>
@endif
