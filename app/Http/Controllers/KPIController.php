<?php

namespace App\Http\Controllers;

use App\Entity;
use App\KPI;
use App\Person;
use App\Task;
use App\Team;
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
        if($kpiId instanceof KPI) {
            // if it is already a KPI just load the relations
            $kpi = $kpiId;
            $kpi->load([
                'values' => function($query) {
                    return $query->limit(50)->newestFirst();
                },
                'values.date',
                'measurable'
            ]);
        } else {
            // get KPI
            $kpi = KPI::with([
                'values' => function($query) {
                    return $query->limit(50)->newestFirst();
                },
                'values.date',
                'measurable'
            ])->findOrFail($kpiId);
        }

        // check permissions
        $this->authorize($kpi);

        // return data
        return ['kpi' => $kpi];
    }
    
    public function bySubtypeAndMeasurable($kpi_type, $kpi_subtype, $measurable_type, $measurable_id) {
        $kpi = KPI::where('type', '=', $kpi_type)->where('subtype', '=', $kpi_subtype)->measurable($measurable_type, $measurable_id)->first();

        return $this->view($kpi);
    }
    
    /**
     * aggregate KPI values
     *
     * @GetParam int[] $measurable_ids required
     * @GetParam int $limit optional
     * @GetParam int $offset optional
     *
     * @param Request $request
     * @param $kpiType
     * @param $kpiSubtype
     * @param $measurableType
     * @param $aggregateBy
     * @param $aggregateFunction
     * @return array
     */
    public function aggregate(Request $request, $kpiType, $kpiSubtype, $measurableType, $aggregateBy, $aggregateFunction) {
        $measurableIds = [];
        foreach($request->input('measurable_ids', []) as $measurableId) {
            // get measurable
            $measurable = $this->getMeasurable($measurableType, $measurableId);

            // check permissions
            $this->authorize('kpis', $measurable);

            // add internal id to measurableIds array
            $measurableIds[] = $measurable->_internal_id;
        }

        if(count($measurableIds) < 1) {
            abort(400, "Missing Measurables");
        }

        // prepare query
        $query = KPI::query()->leftJoin('kpi_values', 'kpis.id', '=', 'kpi_values.kpi_id')->leftJoin('kpi_values_date', 'kpi_values.date_id', '=', 'kpi_values_date.id');

        // add KPI type and subtype where clauses
        $query->where('kpis.type', '=', $kpiType)->where('kpis.subtype', '=', $kpiSubtype);

        // add measurables where clauses
        $query->where('kpis.measurable_type', '=', $measurableType)->whereIn('kpis.measurable_id', $measurableIds);

        // proceed aggregateFunction
        switch($aggregateFunction) {
            case 'Average':
                $query->selectRaw('AVG(`kpi_values`.`value`) as value');
                break;
            
            case 'Total':
            case 'Sum':
            case 'Summa':
                $query->selectRaw('SUM(`kpi_values`.`value`) as value');
                break;
            
            case 'Maximum':
                $query->selectRaw('MAX(`kpi_values`.`value`) as value');
                break;
            
            case 'Minimum':
                $query->selectRaw('MIN(`kpi_values`.`value`) as value');
                break;
            
            case 'Standard Deviation':
            case 'Standard%20Deviation':
                $query->selectRaw('STDDEV_POP(`kpi_values`.`value`) as value');
                break;
            
            case 'Standard Variance':
            case 'Standard%20Variance':
                $query->selectRaw('VAR_POP(`kpi_values`.`value`) as value');
                break;
            
            default:
                abort(420, "Aggregate function not available");
        }
        
        // proceed aggregateBy
        if(in_array($aggregateBy, ['date', 'day', 'week', 'month', 'quarter', 'year', 'dayOfMonth', 'dayOfWeek', 'weekOfMonth', 'weekOfYear', 'monthOfYear', 'quarterOfYear'])) {
            $query->addSelect('kpi_values_date.' . $aggregateBy . ' as dependent')->groupBy('kpi_values_date.' . $aggregateBy)->orderBy('kpi_values_date.' . $aggregateBy, 'DESC');
        } elseif($aggregateBy == "Measurable") {
            switch($measurableType) {
                case 'Person':
                    $query->leftJoin('persons', 'kpis.measurable_id', '=', 'persons._internal_id')->addSelect(DB::raw('CONCAT(`persons`.`first_name`, IFNULL(`persons`.`middle_name`, \'\'), \' \', `persons`.`last_name`) as dependent'))->groupBy('kpis.measurable_id');
                    break;

                case 'Entity':
                    $query->leftJoin('entities', 'kpis.measurable_id', '=', 'entities._internal_id')->addSelect('entities.full_name as dependent')->groupBy('kpis.measurable_id');
                    break;

                case 'Team':
                    $query->leftJoin('teams', 'kpis.measurable_id', '=', 'teams._internal_id')->addSelect('teams.title as dependent')->groupBy('kpis.measurable_id');
                    break;
            }
        } else {
            abort(420, "'Aggregate By' attribute not available");
        }

        // proceed limit query parameter
        $limit = 50;
        if($request->has('limit')) {
            $limit = $request->input('limit');
        }

        // proceed offset query parameter
        $offset = 0;
        if($request->has('offset')) {
            $offset = $request->input('offset');
        }

        // add limit and offset to query
        $query->limit($limit)->offset($offset);

        // get and return date
        return ['values' => $query->get()];
    }

    /**
     * get the values of a KPI
     * 
     * @param $kpiId
     * @return mixed
     */
    public function values(Request $request, $kpiId) {
        // get KPI
        $kpi = KPI::findOrFail($kpiId);

        // check permissions
        $this->authorize($kpi);

        // prepare query
        $values = $kpi->values()->newestFirst();

        // check for limit
        $limit = 50;
        if($request->has('limit')) {
            $limit = $request->input('limit');
        }
        $values->limit($limit);

        // check for offset
        $offset = 0;
        if($request->has('offset')) {
            $offset = $request->input('offset');
        }
        $values->offset($offset);

        // return values
        return ['limit' => $limit, 'offset' => $offset, 'total' => $kpi->values()->count(), 'values' => $values->get()];
    }
    
    private function getMeasurable($measurableType, $measurableId) {
        $attr = 'id';
        if(substr($measurableId, 0, 1) == '_') {
            $attr = '_internal_id';
            $measurableId = substr($measurableId, 1);
        }
        switch($measurableType) {
            case 'Person':
                return Person::where($attr, '=', $measurableId)->firstOrFail();
            
            case 'Team':
                return Team::where($attr, '=', $measurableId)->firstOrFail();
            
            case 'Entity':
                return Entity::where($attr, '=', $measurableId)->firstOrFail();
        }
    }
}
