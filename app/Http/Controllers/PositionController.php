<?php

namespace App\Http\Controllers;

use App\Position;

class PositionController extends Controller
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
     * @return Position
     */
    private function getPosition($id) {
        // when the request uses the internal id the given id starts with an underscore
        if(substr($id, 0, 1) == '_') {
            // get position via _internal_id
            return Position::findOrFail(substr($id, 1));
        } else {
            // get position via GIS id
            return Position::where('id', $id)->firstOrFail();
        }
    }

    /**
     * view a Position
     *
     * @param $positionId
     * @return array
     */
    public function view($positionId) {
        // get position
        $position = $this->getPosition($positionId);

        // load person and team
        $position->load('person', 'team', 'childs', 'childs.person');

        // check permissions
        $this->authorize($position);

        // return data
        return ['position' => $position];
    }
}