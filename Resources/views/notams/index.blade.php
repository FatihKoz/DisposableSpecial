@extends('app')
@section('title', 'Notams')

@section('content')
  @if(!$notams->count())
    <div class="alert alert-info p-1 fw-bold">No Effective NOTAMs Found</div>
  @else
    <div class="row row-cols-3">
      @foreach($notams as $notam)
        <div class="col">
          <div class="card mb-2">
            <div class="card-header p-1">
              <h5 class="m-1">
                {{ $notam->ident }} @if(filled($notam->ref_airline)) | {{ optional($notam->airline)->name }} @endif
                <i class="fas fa-clipboard float-end"></i>
              </h5>
            </div>
            <div class="card-body p-1 text-left">
              <table class="table table-sm table-borderless text-start align-middle mb-0">
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
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  {{ $notams->links('pagination.auto') }}
@endsection
