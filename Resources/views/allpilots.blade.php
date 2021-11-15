@extends('app')
@section('title', trans_choice('common.pilot', 2))

@section('content')
  <div class="row">
    <div class="col">
      <div class="card mb-2">
        <div class="card-header p-1">
          <h5 class="m-1">
            @lang('TurkSim::common.allpilots')
            <i class="fas fa-users float-end"></i>
          </h5>
        </div>
        <div class="card-body p-0 table-responsive">
          @include('users.table')
        </div>
        <div class="card-footer p-0 px-1 small fw-bold text-end">
          @lang('TurkSim::common.totpilots'): {{ $allpilots->total() }}
        </div>
      </div>
    </div>
  </div>

  {{ $allpilots->links('pagination.auto') }}
@endsection
