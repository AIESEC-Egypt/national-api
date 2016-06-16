<?php
namespace App\Policies;

use App\Position;
use App\Person;

class PositionPolicy {

    /**
     * Determine if $user is allowed to view $entity
     *
     * @param Person $user
     * @param Position $position
     * @return bool
     */
    public function view(Person $user, Position $position) {
        if($user->_internal_id === $position->person_id || $user->isCurrentEntity($position->entity()->first()) || $user->isChildInCurrentEntityTree($position->entity()->first())) {
            return true;
        }
        return false;
    }
}