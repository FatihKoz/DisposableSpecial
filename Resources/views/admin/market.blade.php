@extends('admin.app')
@section('title', 'Disposable Market')

@section('content')
  <div class="card border-blue-bottom" style="margin-left:5px; margin-right:5px; margin-bottom:5px;">
    <div class="content">
      <p>Market items managed here. Disposable Special Discord Webhook is used for notifications</p>
      <p>&nbsp;</p>
      <p><a href="https://github.com/FatihKoz" target="_blank">&copy; B.Fatih KOZ</a></p>
    </div>
  </div>

  <div class="row text-center" style="margin:10px;"><h4 style="margin: 5px; padding:0px;"><b>Disposable Market</b></h4></div>

  <div class="row" style="margin-left:5px; margin-right:5px;">
    <div class="card border-blue-bottom" style="padding:10px;">
      {{ Form::open(['route' => 'DSpecial.market_store', 'method' => 'post']) }}
        <input type="hidden" name="item_id" value="{{ $item->id ?? '' }}">
        @if($items->count())
          <div class="row" style="margin-bottom: 10px;">
            <div class="col-sm-5">
              <label class="pl-1 mb-1" for="item_selection">Select an Item for editing</label>
              <select id="item_selection" class="form-control select2" onchange="checkselection()">
                <option value="0">Please Select...</option>
                @foreach($items as $itm)
                  <option value="{{ $itm->id }}" @if($item && $itm->id == $item->id) selected @endif>{{ $itm->id.' : '.$itm->name.' | Price: '.$itm->price.' | Owners: '.$itm->owners_count }} @if($itm->active) (Active) @endif</option>
                @endforeach
              </select>
            </div>
            <div class="col-sm-3 text-left align-middle"><br>
              <a id="edit_link" style="visibility: hidden" href="{{ route('DSpecial.market_admin') }}" class="btn btn-primary pl-1 mb-1">Edit</a>
            </div>
            <div class="col-sm-2 text-right align-middle"><br>
              <a id="delete_link" style="visibility: hidden" href="{{ route('DSpecial.market_admin') }}" class="btn btn-danger pl-1 mb-1">Delete</a>
            </div>
          </div>
        @endif
        <div class="row" style="margin-bottom: 5px;">
          <div class="col-sm-4">
            <label class="pl-1 mb-1" for="item_name">Name <span class="small" title="Mandatory">*</span></label>
            <input name="item_name" type="text" class="form-control" placeholder="Mandatory" maxlength="250" value="{{ $item->name ?? '' }}">
          </div>
          <div class="col-sm-2">
            <label class="pl-1 mb-1" for="eff_start">Price <span class="small" title="Mandatory">*</span></label>
            <input name="item_price" type="number" step="0.01" class="form-control" value="{{ $item->price ?? '' }}">
          </div>
          <div class="col-sm-3">
            @if($airlines)
              <div class="form-group">
                <label class="pl-1 mb-1" for="item_dealer">Dealer (Airline) <span class="small" title="Mandatory">*</span></label>
                <select name="item_dealer" class="form-control select2">
                  <option value="">Select An Airline</option>
                  @foreach($airlines as $airline)
                    <option value="{{ $airline->id }}" @if($item && $item->dealer_id === $airline->id) selected @endif>{{ $airline->code.' | '.$airline->name }}</option>
                  @endforeach
                </select>
              </div>
            @endif
          </div>
          <div class="col-sm-3">
            @if($categories)
              <div class="form-group">
                <label class="pl-1 mb-1" for="item_category">Category (Optional)</label>
                {{ Form::select('item_category', $categories, $item->category ?? '', ['class' => 'form-control select2']) }}
              </div>
            @endif
          </div>
        </div>
        <div class="row">
          <div class="col-sm-6">
            <label class="pl-1 mb-1" for="item_description">Item Description (Public)</label>
            <textarea id="editor_desc" name="item_description" class="editor">{!! $item->description ?? '' !!}</textarea>
          </div>
          <div class="col-sm-6">
            <label class="pl-1 mb-1" for="item_notes">Item Notes (visible to Owners)</label>
            <textarea id="editor_notes" name="item_notes" class="editor">{!! $item->notes ?? '' !!}</textarea>
          </div>
        </div>
        <div class="row">
          <div class="col-sm-6">
            <label class="pl-1 mb-1" for="item_image_url">Image URL or PATH</label>
            <input name="item_image_url" type="text" class="form-control mb-1" placeholder="Optional" maxlength="250" value="{{ $item->image_url ?? '' }}">
          </div>
          <div class="col-sm-3">  
            <label class="pl-1 mb-1" for="item_active">Active</label>
            <input type="hidden" name="item_active" value="0">
            <input name="item_active" type="checkbox" @if($item && $item->active == 1) checked="true" @endif class="form-control mb-1" value="1">
          </div>
          <div class="col-sm-3">
            <label class="pl-1 mb-1" for="item_notifications">Notifications</label>
            <input type="hidden" name="item_notifications" value="0">
            <input name="item_notifications" type="checkbox" @if($item && $item->notifications == 1) checked="true" @endif class="form-control mb-1" value="1">
          </div>
        </div>
        <div class="row" style="margin-bottom: 10px;">
          <div class="col-sm-12 text-right">
            <button class="btn btn-primary pl-1 mb-1" type="submit">@if($item && $item->id) Update @else Save @endif</button>
          </div>
        </div>
      {{ Form::close() }}
    </div>
  </div>

  @if(filled($item) && $item->owners->count() > 0)
    <div class="row" style="margin-left:5px; margin-right:5px;">
      <div class="card border-blue-bottom" style="padding:10px;">
        <b>Owners</b><hr>
        @foreach($item->owners as $owner)
          &bull; {{ $owner->ident.' | '.$owner->name_private }}<br>
        @endforeach
      </div>
    </div>
  @endif

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
      if (document.getElementById("item_selection").value === "0") {
        document.getElementById('edit_link').style.visibility = 'hidden';
        document.getElementById('delete_link').style.visibility = 'hidden';
      } else {
        document.getElementById('edit_link').style.visibility = 'visible';
        document.getElementById('delete_link').style.visibility = 'visible';
      }
      const selected = document.getElementById("item_selection").value;
      const editlink = "?itemedit=".concat(selected);
      const deletelink = "?itemdelete=".concat(selected);

      document.getElementById("edit_link").href = $oldlink.concat(editlink);
      document.getElementById("delete_link").href = $oldlink.concat(deletelink);
    }
  </script>
  <script src="{{ public_asset('assets/vendor/ckeditor4/ckeditor.js') }}"></script>
  <script>$(document).ready(function () { CKEDITOR.replace('editor_desc'); });</script>
  <script>$(document).ready(function () { CKEDITOR.replace('editor_notes'); });</script>
@endsection
