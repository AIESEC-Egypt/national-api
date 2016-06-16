<?php
namespace App\Policies;

use App\Entity;
use App\Person;

class EntityPolicy {

    /**
     * Determine if $user is allowed to view $entity
     *
     * @param Person $user
     * @param Entity $entity
     * @return bool
     */
    public function view(Person $user, Entity $entity) {
        return true;
    }

    /**
     * Determines if $user is allowed to view the KPIs of $entity and their latest Value
     *
     * @param Person $user
     * @param Entity $entity
     * @return true
     */
    public static function kpis(Person $user, Entity $entity) {
        if($entity->isCurrentEB($user) || $user->isChildInCurrentEntityTree($entity)) {
            return true;
        }
        return false;
    }
}