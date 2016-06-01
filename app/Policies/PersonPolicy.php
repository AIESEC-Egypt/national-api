<?php
/**
 * Created by PhpStorm.
 * User: kjs
 * Date: 01.06.16
 * Time: 17:59
 */
namespace App\Policies;

use App\Person;

class PersonPolicy {
    /**
     * Determine if $user is allowed to view $person
     *
     * @param Person $user
     * @param Person $person
     * @return bool
     */
    public function view(Person $user, Person $person) {
        return true;
    }

    /**
     * Determine if $user is allowed to list $person's tasks
     *
     * @param Person $user
     * @param Person $person
     * @return bool
     */
    public function listTasks(Person $user, Person $person) {
        return $user->_internal_id === $person->_internal_id;
    }

    /**
     * Determines if $user is allowed to add $person a task
     *
     * @param Person $user
     * @param Person $person
     * @return bool
     */
    public function addTask(Person $user, Person $person) {
        return $user->_internal_id === $person->_internal_id;
    }
}