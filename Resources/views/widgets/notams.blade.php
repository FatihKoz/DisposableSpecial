<div class="card mb-2">
  <div class="card-header p-1">
    <h5 class="m-1">
      NOTAMs
      <i class="fas fa-clipboard float-end"></i>
    </h5>
  </div>
  <div class="card-body overflow-auto text-center p-1">
    @if($notams->count() > 0)
      @foreach($notams as $notam)
        @if(!$loop->first) <hr class="m-0 p-0 my-1"/> @endif
        <table class="table table-sm table-borderless text-start mb-0">
          <tr>
            <th class="m-0 p-0" colspan="2">{{ $notam->ident }} @if(filled($notam->ref_airline)){{ ' | '.optional($notam->airline)->name }}@endif</th>
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
      @endforeach
    @else
      <span><a href="{{ route('DSpecial.notams') }}">No NOTAMs Found ! Check All Notams</a></span>
    @endif
  </div>
</div>
