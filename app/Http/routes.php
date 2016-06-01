<?php
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

/**
 * Person routes (v1)
 */
// GET /v1/persons/{person_id}.json
$app->get('v1/persons/{person_id}.json', ['as' => 'get_person', 'uses' => 'PersonController@view']);

$app->group(['prefix' => 'v1/persons/{person_id}', 'namespace' => 'App\Http\Controllers'], function () use ($app) {
    // GET /v1/persons/{person_id}/tasks.json
    $app->get('tasks.json', ['as' => 'person_task_list', 'uses' => 'PersonController@listTasks']);

    // POST /v1/persons/{person_id}/tasks.json
    $app->post('tasks.json', ['as' => 'person_add_task', 'uses' => 'PersonController@addTask']);
});

/**
 * Tasks routes (v1)
 */
// GET /v1/tasks/{task_id}.json
$app->get('v1/tasks/{task_id}.json', ['as' => 'get_task', 'uses' => 'TaskController@view']);

// UPDATE /v1/tasks/{task_id}.json
$app->delete('v1/tasks/{task_id}.json', ['as' => 'update_task', 'uses' => 'TaskController@update']);

// DELETE /v1/tasks/{task_id}.json
$app->delete('v1/tasks/{task_id}.json', ['as' => 'delete_task', 'uses' => 'TaskController@delete']);

$app->group(['prefix' => 'v1/tasks/{task_id}', 'namespace' => 'App\Http\Controllers'], function () use ($app) {
    // POST /v1/tasks/{task_id}/done.json
    $app->post('done.json', ['as' => 'mark_task_done', 'uses' => 'TaskController@done']);

    // POST /v1/tasks/{task_id}/approve.json
    $app->post('approve.json', ['as' => 'approve_task', 'uses' => 'TaskController@approve']);
});

/**
 * List routes (v1)
 */
$app->group(['prefix' => 'v1/lists', 'namespace' => 'App\Http\Controllers'], function () use ($app) {

});
