@extends('app')
@section('title', __('common.dashboard'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <form class="form" action="{{ route('DSpecial.calc_trl.calcTrl') }}" method="post">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0 d-flex align-items-center">
                        Calcul Transition Level
                        <i class="fas fa-question-circle ms-auto"></i>
                    </h5>
                </div>
                <div class="card-body">
                    @csrf
                    <div class="mb-3">
                        <label for="qnh" class="form-label">QNH Actuel</label>
                        <div class="input-group">
                            <input class="form-control" name="qnh" id="qnh" size="5" type="number" placeholder="{{ $qnh }}" minlength="3" maxlength="4">
                            <span class="input-group-text">Hpa</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="ta" class="form-label">Transition Altitude</label>
                        <div class="input-group">
                            <input class="form-control" name="ta" id="ta" size="5" type="number" placeholder="{{ $ta }}" minlength="4" maxlength="5">
                            <span class="input-group-text">Ft</span>
                        </div>
                    </div>
                    <div class="text-center">
                        <button name="btntrl" type="submit" id="btntrl" class="btn btn-sm btn-success"><i class="fas fa-solid fa-plane me-2"></i> Calc TRL</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@if($calcTrl)
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0 text-center">Résultats</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col"></div> <!-- Colonne vide à gauche -->
                    <div class="col-md-6"> <!-- Colonne centrale -->
                        <div class="table-responsive">
                            <table class="table table-sm table-striped text-center">
                                <tbody>
                                    <tr>
                                        <td><strong>Altitude 1013 :</strong> {{ $alt1013 }} Ft</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped text-center">
                                <tbody>
                                    <tr>
                                        <td><strong>FLeq10</strong></td>
                                        <td><strong>TRL</strong></td>
                                        <td><strong>FLeq20</strong></td>
                                    </tr>
                                    <tr>
                                        <td>FL{{ $flEq10 }}</td>
                                        <td>FL{{ $trl }}</td>
                                        <td>FL{{ $flEq20 }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <hr>
                        <div class="text-center">
                            <h6>Résultat final :</h6>
                            <p>Ta TRL est <strong>FL{{ $trl }}</strong></p>
                        </div>
                    </div>
                    <div class="col"></div> <!-- Colonne vide à droite -->
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection
