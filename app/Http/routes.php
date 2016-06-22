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

/*
 * Person routes (v1)
 */
// GET /v1/persons/autocomplete.json
$app->get('v1/persons/autocomplete.json', ['as' => 'persons_autocomplete', 'uses' => 'PersonController@autocomplete']);

// GET /v1/persons/{person_id}.json
$app->get('v1/persons/{person_id}.json', ['as' => 'get_person', 'uses' => 'PersonController@view']);

$app->group(['prefix' => 'v1/persons/{person_id}', 'namespace' => 'App\Http\Controllers'], function () use ($app) {
    // GET /v1/persons/{person_id}/tasks.json
    $app->get('tasks.json', ['as' => 'person_task_list', 'uses' => 'PersonController@listTasks']);

    // POST /v1/persons/{person_id}/tasks.json
    $app->post('tasks.json', ['as' => 'person_add_task', 'uses' => 'PersonController@addTask']);
    
    // GET /v1/persons/{person_id}/positions.json
    $app->get('positions.json', ['as' => 'person_positions', 'uses' => 'PersonController@positions']);

    // GET /v1/persons/{person_id}/kpis.json
    $app->get('kpis.json', ['as' => 'person_kpis', 'uses' => 'PersonController@kpis']);

    // GET /v1/persons/{person_id}/managing.json
    $app->get('managing.json', ['as' => 'person_managing', 'uses' => 'PersonController@managing']);
});

/*
 * Tasks routes (v1)
 */
// POST /v1/tasks/prioritize.json
$app->post('v1/tasks/prioritize.json', ['as' => 'prioritize_tasks', 'uses' => 'TaskController@prioritize']);

// GET /v1/tasks/{task_id}.json
$app->get('v1/tasks/{task_id}.json', ['as' => 'get_task', 'uses' => 'TaskController@view']);

// POST /v1/tasks/{task_id}.json
$app->post('v1/tasks/{task_id}.json', ['as' => 'update_task', 'uses' => 'TaskController@update']);

// DELETE /v1/tasks/{task_id}.json
$app->delete('v1/tasks/{task_id}.json', ['as' => 'delete_task', 'uses' => 'TaskController@delete']);

$app->group(['prefix' => 'v1/tasks/{task_id}', 'namespace' => 'App\Http\Controllers'], function () use ($app) {
    // POST /v1/tasks/{task_id}/done.json
    $app->post('done.json', ['as' => 'mark_task_done', 'uses' => 'TaskController@done']);

    // POST /v1/tasks/{task_id}/approve.json
    $app->post('approve.json', ['as' => 'approve_task', 'uses' => 'TaskController@approve']);
});

/*
 * KPI routes (v1)
 */
$app->group(['prefix' => 'v1/kpis', 'namespace' => 'App\Http\Controllers'], function () use ($app) {

    // GET /v1/kpis/bySubtypeAndMeasurable/{kpi_type}/{kpi_subtype}/{measurable_type}/{measurable_id}.json
    $app->get('bySubtypeAndMeasurable/{kpi_type}/{kpi_subtype}/{measurable_type}/{measurable_id}.json', ['as' => 'get_kpi_by_subtype_and_measurable', 'uses' => 'KPIController@bySubtypeAndMeasurable']);

    // GET /v1/kpis/aggregate/{kpi_type}/{kpi_subtype}/{measurable_type}/{aggregate_by}/{aggregate_function}.json
    $app->get('aggregate/{kpi_type}/{kpi_subtype}/{measurable_type}/{aggregate_by}/{aggregate_function}.json', ['as' => 'kpi_aggregate', 'uses' => 'KPIController@aggregate']);

    // GET /v1/kpis/{kpi_id}.json
    $app->get('{kpi_id}.json', ['as' => 'get_kpi', 'uses' => 'KPIController@view']);

    // GET /v1/kpis/{kpi_id}/values.json
    $app->get('{kpi_id}/values.json', ['as' => 'get_kpi_values', 'uses' => 'KPIController@values']);
});

/*
 * Entity routes (v1)
 */
// GET /v1/entities/autocomplete.json
$app->get('v1/entities/autocomplete.json', ['as' => 'entities_autocomplete', 'uses' => 'EntityController@autocomplete']);

// GET /v1/entities/{entity_id}.json
$app->get('v1/entities/{entity_id}.json', ['as' => 'get_entity', 'uses' => 'EntityController@view']);

// GET /v1/entities/{entity_id}/kpis.json
$app->get('v1/entities/{entity_id}/kpis.json', ['as' => 'get_entity_kpis', 'uses' => 'EntityController@kpis']);


/*
 * Position routes (v1)
 */
// GET /v1/positions/{position_id}.json
$app->get('v1/positions/{position_id}.json', ['as' => 'get_position', 'uses' => 'PositionController@view']);

/*
 * Team routes (v1)
 */
// GET /v1/teams/autocomplete.json
$app->get('v1/teams/autocomplete.json', ['as' => 'teams_autocomplete', 'uses' => 'TeamController@autocomplete']);

// GET /v1/teams/{team_id}.json
$app->get('v1/teams/{team_id}.json', ['as' => 'get_team', 'uses' => 'TeamController@view']);

// GET /v1/teams/{team_id}/kpis.json
$app->get('v1/teams/{team_id}/kpis.json', ['as' => 'get_team_kpis', 'uses' => 'TeamController@kpis']);

// GET /v1/teams/{team_id}/positions.json
$app->get('v1/teams/{team_id}/positions.json', ['as' => 'get_team_positions', 'uses' => 'TeamController@positions']);

