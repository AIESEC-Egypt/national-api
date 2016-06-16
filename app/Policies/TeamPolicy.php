<?php
namespace App\Policies;

use App\Team;
use App\Person;

class TeamPolicy {
    /**
     * Determines if $user can view $team
     *
     * @param Person $user
     * @param Team $team
     * @return bool
     */
    public function view(Person $user, Team $team) {
        if( $user->isCurrentEntity($team->entity()->first()) || $user->isChildInCurrentEntityTree($team->entity()->first())) {
            return true;
        }
        return false;
    }

    /**
     * Determines if $user can view the KPIs of $team
     *
     * @param Person $user
     * @param Team $team
     * @return bool
     */
    public static function kpis(Person $user, Team $team) {
        if( $team->inTopPosition($user) || $team->entity()->first()->isCurrentEB($user) || $user->isChildInCurrentEntityTree($team->entity()->first())) {
            return true;
        }
        return false;
    }

    /**
     * Determines if $user can view the positions of $team
     *
     * @param Person $user
     * @param Team $team
     * @return bool
     */
    public function positions(Person $user, Team $team) {
        if( $user->isCurrentEntity($team->entity()->first()) || $user->isChildInCurrentEntityTree($team->entity()->first())) {
            return true;
        }
        return false;
    }
}