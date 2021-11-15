@extends('admin.app')
@section('title', 'Disposable Notams')

@section('content')
  <div class="card border-blue-bottom" style="margin-left:5px; margin-right:5px; margin-bottom:5px;">
    <div class="content">
      <p>Notams can be entered for an Airport or for an Airline (like Company Notams)</p>
      <p>&nbsp;</p>
      <p><a href="https://github.com/FatihKoz" target="_blank">&copy; B.Fatih KOZ</a></p>
    </div>
  </div>

  <div class="row text-center" style="margin:10px;"><h4 style="margin: 5px; padding:0px;"><b>Disposable Notams</b></h4></div>

  <div class="row" style="margin-left:5px; margin-right:5px;">
    <div class="card border-blue-bottom" style="padding:10px;">
      {{ Form::open(['route' => 'DSpecial.notam_store', 'method' => 'post']) }}
        <input type="hidden" name="notam_id" value="{{ $notam->id ?? '' }}">
        @if($notams->count())
          <div class="row" style="margin-bottom: 10px;">
            <div class="col-sm-5">
              <label class="pl-1 mb-1" for="notam_selection">Select Pre-Recorded Notam For Editing</label>
              <select id="notam_selection" class="form-control select2" onchange="checkselection()">
                <option value="0">Please Select...</option>
                @foreach($notams as $ntm)
                  <option value="{{ $ntm->id }}" @if($notam &&  $ntm->id == $notam->id) selected @endif>{{ $ntm->id.' : '.$ntm->title }} @if($ntm->active) (Active) @endif</option>
                @endforeach
              </select>
            </div>
            <div class="col-sm-3 text-left align-middle"><br>
              <a id="edit_link" style="visibility: hidden" href="{{ route('DSpecial.notam_admin') }}" class="btn btn-primary pl-1 mb-1">Edit</a>
            </div>
            <div class="col-sm-2 text-right align-middle"><br>
              <a id="delete_link" style="visibility: hidden" href="{{ route('DSpecial.notam_admin') }}" class="btn btn-danger pl-1 mb-1">Delete</a>
            </div>
          </div>
        @endif

        <div class="row" style="margin-bottom: 5px;">
          <div class="col-sm-8">
            <label class="pl-1 mb-1" for="notam_title">Notam Title</label>
            <input name="notam_title" type="text" class="form-control" placeholder="Mandatory" maxlength="150" value="{{ $notam->title ?? '' }}">
          </div>
        </div>

        <div class="row">
          <div class="col-sm-8">
            <label class="pl-1 mb-1" for="notam_body">Notam Body</label>
            <textarea id="editor" name="notam_body" class="editor">{!! $notam->body ?? '' !!}</textarea>
          </div>
          <div class="col-sm-4">
            <div class="col-sm-6">
              <label class="pl-1 mb-1" for="eff_start">Effective From</label>
              <div class="input-group input-group-sm">
                <input name="eff_start" type="date" class="form-control" value="{{ $notam->eff_start ?? '' }}">
                <input name="eff_stime" type="text" class="form-control" maxlenght="4" placeholder="1530"  value="{{ $notam->eff_stime ?? '' }}">
              </div>
            </div>
            <div class="col-sm-6">
              <label class="pl-1 mb-1" for="eff_end">Effective Until</label>
              <div class="input-group input-group-sm">
                <input name="eff_end" type="date" class="form-control" value="{{ $notam->eff_end ?? '' }}">
                <input name="eff_etime" type="text" class="form-control" maxlenght="4" placeholder="2330"  value="{{ $notam->eff_etime ?? '' }}">
              </div>
            </div>
            @if($airports)
              <div class="form-group">
                <label class="pl-1 mb-1" for="ref_airport">Effective for Airport</label>
                <select name="ref_airport" class="form-control select2">
                  <option value="">Select An Airport (Optional)</option>
                  @foreach($airports as $airport)
                    <option value="{{ $airport->id }}" @if($notam && $notam->ref_airport === $airport->id) selected @endif>{{ $airport->id.' | '.$airport->name }}</option>
                  @endforeach
                </select>
              </div>
            @endif
            @if($airlines)
              <div class="form-group">
                <label class="pl-1 mb-1" for="ref_airline">Effective for Airline</label>
                <select name="ref_airline" class="form-control select2">
                  <option value="">Select An Airline (Optional)</option>
                  @foreach($airlines as $airline)
                    <option value="{{ $airline->id }}" @if($notam && $notam->ref_airline === $airline->id) selected @endif>{{ $airline->code.' | '.$airline->name }}</option>
                  @endforeach
                </select>
              </div>
            @endif
            @if($notams)
            <div class="form-group">
              <label class="pl-1 mb-1" for="ref_notamid">Replacing Notam</label>
              <select name="ref_notamid" class="form-control select2">
                <option value="">Select An Old Notam (Optional)</option>
                @foreach($notams as $oldnotam)
                  <option value="{{ $oldnotam->id }}" @if($notam && $notam->ref_notamid === $oldnotam->id) selected @endif>{{ $oldnotam->id.' | '.$oldnotam->title }}</option>
                @endforeach
              </select>
            </div>
          @endif
          </div>

        </div>

        <div class="row" style="margin-bottom: 10px;">
          <div class="col-sm-2 text-left">
            <input type="hidden" name="active" value="0">
            <label class="pl-1 mb-1" for="active">Active <input name="active" type="checkbox" @if($notam && $notam->active == 1) checked="true" @endif class="form-control" value="1"></label>
          </div>
          <div class="col-sm-10 text-right">
            <button class="btn btn-primary pl-1 mb-1" type="submit">@if($notam && $notam->id) Update @else Save @endif</button>
          </div>
        </div>
      {{ Form::close() }}
    </div>
  </div>

  <style>
    ::placeholder { color: indianred !important; opacity: 0.6 !important; }
    :-ms-input-placeholder { color: indianred !important; }
    ::-ms-input-placeholder { color: indianred !important; }
  </style>
@endsection
@section('scripts')
  @parent
  <script type="text/javascript">
    // Simple Selection With Dropdown Change
    // Also keep button hidden until a valid selection
    const $oldlink = document.getElementById("edit_link").href;

    function checkselection() {
      if (document.getElementById("notam_selection").value === "0") {
        document.getElementById('edit_link').style.visibility = 'hidden';
        document.getElementById('delete_link').style.visibility = 'hidden';
      } else {
        document.getElementById('edit_link').style.visibility = 'visible';
        document.getElementById('delete_link').style.visibility = 'visible';
      }
      const selected = document.getElementById("notam_selection").value;
      const editlink = "?editntm=".concat(selected);
      const deletelink = "?deletentm=".concat(selected);

      document.getElementById("edit_link").href = $oldlink.concat(editlink);
      document.getElementById("delete_link").href = $oldlink.concat(deletelink);
    }
  </script>
  <script src="{{ public_asset('assets/vendor/ckeditor4/ckeditor.js') }}"></script>
  <script>$(document).ready(function () { CKEDITOR.replace('editor'); });</script>
@endsection
