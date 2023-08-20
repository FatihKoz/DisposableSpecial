<?php

namespace Modules\DisposableSpecial\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Airline;
use App\Models\Airport;
use Carbon\Carbon;
use Modules\DisposableSpecial\Models\DS_Notam;
use Illuminate\Http\Request;

class DS_NotamController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $where = [];
        $where['active'] = 1;

        $notam_id = $request->input('id');
        $ref_airport = $request->input('airport');
        $ref_airline = $request->input('airline');

        if (is_numeric($notam_id)) {
            $where['id'] = $notam_id;
        }
        if (!empty($ref_airport)) {
            $where['ref_airport'] = $ref_airport;
        }
        if (!empty($ref_airline)) {
            $airline = Airline::select('id')->where('icao', $ref_airline)->first();
            if (!empty($airline)) {
                $where['ref_airline'] = $airline->id;
            }
        }

        $notams = DS_Notam::with('airline', 'airport')->where($where)
            ->where(function ($query) use ($now) {
                $query->whereDate('eff_start', '<=', $now)->whereDate('eff_end', '>=', $now)
                    ->orWhereDate('eff_start', '<=', $now)->whereNull('eff_end');
            })->orderby('updated_at', 'desc')
            ->paginate(15);

        $remove_array = array('<p>', '</p>', '<br>', '<br/>', '<br />', '<hr>', '<hr/>', '<hr />');

        return view('DSpecial::notams.index', [
            'notams' => $notams,
            'remove' => $remove_array,
        ]);
    }

    public function index_admin(Request $request)
    {
        if ($request->input('deletentm')) {
            DS_Notam::where('id', $request->input('deletentm'))->delete();
            flash()->warning('Notam Deleted !');
            return redirect(route('DSpecial.notam_admin'));
        }

        $notams = DS_Notam::get();
        $airlines = Airline::select('id', 'name', 'icao', 'iata')->orderby('name')->get();
        $airports = Airport::select('id', 'name')->orderby('id')->get();

        if ($request->input('editntm')) {
            $notam = DS_Notam::where('id', $request->input('editntm'))->first();

            if (!isset($notam)) {
                flash()->error('Notam Not Found !');
                return redirect(route('DSpecial.notam_admin'));
            }
        }

        return view('DSpecial::admin.notams', [
            'airlines' => isset($airlines) ? $airlines : null,
            'airports' => isset($airports) ? $airports : null,
            'notams'   => $notams,
            'notam'    => isset($notam) ? $notam : null,
        ]);
    }

    // Store Notam
    public function store(Request $request)
    {

        if (!$request->notam_title || !$request->notam_body || !$request->eff_start) {
            flash()->error('Title, Notam Body and Effective From fields are mandatory !');
            return redirect(route('DSpecial.notam_admin'));
        }

        DS_Notam::updateOrCreate(
            [
                'id' => $request->notam_id,
            ],
            [
                'title'       => $request->notam_title,
                'body'        => $request->notam_body,
                'eff_start'   => $request->eff_start,
                'eff_stime'   => $request->eff_stime,
                'eff_end'     => $request->eff_end,
                'eff_etime'   => $request->eff_etime,
                'ref_airport' => $request->ref_airport,
                'ref_airline' => $request->ref_airline,
                'ref_notamid' => $request->ref_notamid,
                'active'      => $request->active,
            ]
        );

        flash()->success('Notam Saved');
        return redirect(route('DSpecial.notam_admin'));
    }
}
