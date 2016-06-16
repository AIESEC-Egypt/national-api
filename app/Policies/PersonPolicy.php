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
        if($user->positions()->current()->count() > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine if $user is allowed to list $person's tasks
     *
     * @param Person $user
     * @param Person $person
     * @return bool
     */
    public function listTasks(Person $user, Person $person) {
        if($user->_internal_id === $person->_internal_id || $user->isLeaderFor($person) || $user->isManagerOf($person)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determines if $user is allowed to add $person a task
     *
     * @param Person $user
     * @param Person $person
     * @return bool
     */
    public function addTask(Person $user, Person $person) {
        if($user->_internal_id === $person->_internal_id || $user->isLeaderFor($person) || $user->isManagerOf($person)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determines if $user is allowed to view the positions of $person
     *
     * @param Person $user
     * @param Person $person
     * @return bool
     */
    public function positions(Person $user, Person $person) {
        if($user->_internal_id === $person->_internal_id || $user->isLeaderFor($person) || $user->isManagerOf($person)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determines if $user can view the childs of $person 's positions
     *
     * @param Person $user
     * @param Person $person
     * @return bool
     */
    public function positions_childs(Person $user, Person $person) {
        return $user->_internal_id === $person->_internal_id;
    }

    /**
     * Determines if $user can view the KPIs of the childs of any position of $person
     *
     * @param Person $user
     * @param Person $person
     * @return bool
     */
    public function positions_kpis(Person $user, Person $person) {
        return false;
    }

    /**
     * Determines if $user can view the KPIs of the childs of the current positions of $person
     *
     * @param Person $user
     * @param Person $person
     * @return bool
     */
    public function positions_kpis_current(Person $user, Person $person) {
        return $user->_internal_id === $person->_internal_id;
    }

    /**
     * Determines if $user can retrieve $person's KPIs
     *
     * @param Person $user
     * @param Person $person
     * @return bool
     */
    public static function kpis(Person $user, Person $person) {
        if($user->_internal_id === $person->_internal_id || $user->isLeaderFor($person) || $user->isManagerOf($person)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determines if $user can retrieve which persons $person is managing
     *
     * @param Person $user
     * @param Person $person
     * @return bool
     */
    public function managing(Person $user, Person $person) {
        if($user->_internal_id === $person->_internal_id || $user->isLeaderFor($person)) {
            return true;
        } else {
            return false;
        }
    }
}