<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        return $this->ok([]);
    }

    public function assign(Request $request, $user)
    {
        return $this->ok(null, 'Not implemented', 501);
    }
}
