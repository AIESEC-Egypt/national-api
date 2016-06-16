<?php

namespace App\Http\Controllers;

use App\KPI;
use App\Person;
use App\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KPIController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * view a KPI
     *
     * @param $kpiId
     * @return array
     */
    public function view($kpiId) {
        // get KPI
        $kpi = KPI::with([
            'values' => function($query) {
                return $query->limit(100);
            },
            'values.date',
            'measurable'
        ])->findOrFail($kpiId);

        // check permissions
        $this->authorize($kpi);

        // return data
        return ['kpi' => $kpi];
    }

    /**
     * get the values of a KPI
     * 
     * @param $kpiId
     * @return mixed
     */
    public function values($kpiId) {
        // get KPI
        $kpi = KPI::findOrFail($kpiId);

        // check permissions
        $this->authorize($kpi);

        // return values
        return $kpi->values()->paginate(100);
    }
}
