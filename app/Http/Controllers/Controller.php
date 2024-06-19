<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function loadViewData()
    {
        $viewData = [];

        // Check for flash errors and add to view if present
        if (session('error')) {
            $viewData['error'] = session('error');
            $viewData['errorDetail'] = session('errorDetail');
        }

        // Check for logged on user and set some convenience view vars
        if (session('userName')) {
            $viewData['userName'] = session('userName');
            $viewData['userIdentifier'] = session('userIdentifier');
        }

        return $viewData;
    }


}
