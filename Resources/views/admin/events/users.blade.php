<div id="event_users_wrapper" class="dataTables_wrapper form-inline dt-bootstrap col-lg-12">
  @if(count($event->users) === 0)
    @include('admin.common.none_added', ['type' => 'pilots'])
  @endif

  <table class="table table-responsive" id="users-table">
    @if(count($event->users))
      <thead>
      <th>Name</th>
      <th style="text-align: center;">Actions</th>
      </thead>
    @endif
    <tbody>
    @foreach($event->users as $user)
      <tr>
        <td>{{ $user->name }}</td>
        <td style="width: 10%; text-align: center;" class="form-inline">
          {{ Form::open(['url' => '/admin/devent_admin/'.$event->id.'/users', 'method' => 'delete', 'class' => 'pjax_form']) }}
          {{ Form::hidden('user_id', $user->id) }}
          <div class='btn-group'>
            {{ Form::button('<i class="fa fa-times"></i>',
                             ['type' => 'submit',
                              'class' => 'btn btn-sm btn-danger btn-icon'])
              }}
          </div>
          {{ Form::close() }}
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
  <hr/>
  <div class="row">
    @include('DSpecial::admin.events.users_actions')
  </div>
</div>
<script>
  $(document).ready(function () {
    $('.select2').select2();
  });
</script>
