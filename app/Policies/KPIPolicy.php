<?php
namespace App\Policies;

use App\KPI;
use App\Person;

class KPIPolicy {
    /**
     * Determine if $user is allowed to view $kpi
     *
     * @param Person $user
     * @param KPI $kpi
     * @return bool
     */
    public function view(Person $user, KPI $kpi) {
        switch($kpi->measurable_type) {
            case 'App\Person':
                return PersonPolicy::kpis($user, $kpi->measurable);
            
            case 'App\Entity':
                return EntityPolicy::kpis($user, $kpi->measurable);

            case 'App\Team':
                return TeamPolicy::kpis($user, $kpi->measurable);
        }
        return false;
    }

    /**
     * Determine if $user is allowed to view the values of $kpi
     *
     * @param Person $user
     * @param KPI $kpi
     * @return bool
     */
    public function values(Person $user, KPI $kpi) {
        return $this->view($user, $kpi);
    }
}