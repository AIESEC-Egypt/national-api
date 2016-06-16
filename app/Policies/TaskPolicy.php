<?php
/**
 * Created by PhpStorm.
 * User: kjs
 * Date: 01.06.16
 * Time: 17:24
 */

namespace App\Policies;

use App\Person;
use App\Task;

class TaskPolicy {
    /**
     * Determine if a person can view a task
     *
     * @param Person $person
     * @param Task $task
     * @return bool
     */
    public function view(Person $person, Task $task) {
        if(is_null($task->deleted_at)) {
            if( $task->person_id === $person->_internal_id || $task->added_by === $person->_internal_id || $person->isLeaderFor($task->_person_id) || $person->isManagerOf($task->person_id) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if a person can mark a task as done
     *
     * @param Person $person
     * @param Task $task
     * @return bool
     */
    public function done(Person $person, Task $task) {
        if(!$task->approved && !$task->done && is_null($task->deleted_at)) {
            if( $task->person_id === $person->_internal_id || $task->added_by === $person->_internal_id || $person->isLeaderFor($task->_person_id) || $person->isManagerOf($task->person_id) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if a person can delete a task
     *
     * @param Person $person
     * @param Task $task
     * @return bool
     */
    public function delete(Person $person, Task $task) {
        if(!$task->approved && !$task->done && is_null($task->deleted_at)) {
            if( $task->added_by === $person->_internal_id || $person->isLeaderFor($task->_person_id) || $person->isManagerOf($task->person_id) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if a person can update a task
     *
     * @param Person $person
     * @param Task $task
     * @return bool
     */
    public function update(Person $person, Task $task) {
        if(!$task->approved && is_null($task->deleted_at)) {
            if( $task->person_id === $person->_internal_id || $task->added_by === $person->_internal_id || $person->isLeaderFor($task->_person_id) || $person->isManagerOf($task->person_id) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if a person can update the estimated time of a task
     *
     * @param Person $person
     * @param Task $task
     * @return bool
     */
    public function update_estimated(Person $person, Task $task) {
        if(!$task->approved && !$task->done && is_null($task->deleted_at)) {
            if( $task->added_by === $person->_internal_id || $person->isLeaderFor($task->_person_id) || $person->isManagerOf($task->person_id) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if a person can update the due date of a task
     *
     * @param Person $person
     * @param Task $task
     * @return bool
     */
    public function update_due(Person $person, Task $task) {
        if(!$task->approved && !$task->done && is_null($task->deleted_at)) {
            if( $task->added_by === $person->_internal_id || $person->isLeaderFor($task->_person_id) || $person->isManagerOf($task->person_id) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if a person can approve a task
     *
     * @param Person $person
     * @param Task $task
     * @return bool
     */
    public function approve(Person $person, Task $task) {
        if(!$task->approved && is_null($task->deleted_at)) {
            if( ($task->person_id != $task->added_by && $task->added_by === $person->_internal_id) || $person->isLeaderFor($task->_person_id) || $person->isManagerOf($task->person_id) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if a person is allowed to prioritize a task
     *
     * @param Person $person
     * @param Task $task
     * @return bool
     */
    public function prioritize(Person $person, Task $task) {
        return $task->person_id === $person->_internal_id;
    }
}