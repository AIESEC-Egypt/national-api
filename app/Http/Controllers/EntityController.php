<?php

namespace App\Http\Controllers;

use App\Entity;
use Illuminate\Support\Facades\Gate;

class EntityController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * determines which kind of id is given and returns the corresponding entity
     *
     * @param $id
     * @return Entity
     */
    private function getEntity($id) {
        // when the request uses the internal id the given id starts with an underscore
        if(substr($id, 0, 1) == '_') {
            // get entity via _internal_id
            return Entity::findOrFail(substr($id, 1));
        } else {
            // get entity via GIS id
            return Entity::where('id', $id)->firstOrFail();
        }
    }

    /**
     * view a Entity
     *
     * @param $entityId
     * @return array
     */
    public function view($entityId) {
        // get entity
        $entity = $this->getEntity($entityId);

        // check permissions
        $this->authorize($entity);

        // load direct childs
        $entity->load('directChilds', 'terms');

        // directly load kpis if user is allowed to see them
        if(Gate::allows('kpis', $entity)) {
            $entity->load('kpis', 'kpis.latestValue');
        }

        // return data
        return ['entity' => $entity];
    }

    /**
     * Returns the KPIs of the Entity with their latest value
     *
     * @param $entityId
     * @return array
     */
    public function kpis($entityId) {
        // get entity
        $entity = $this->getEntity($entityId);

        // check permissions
        $this->authorize($entity);

        // return data
        return ['kpis' => $entity->kpis()->with('latestValue')->get];
    }
}