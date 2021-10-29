<?php

namespace App\Http\Controllers\TTR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TTR\Behavior;

class BookController extends Controller
{
    //
    public function book() {
        $data = Behavior::all();
        return response()->json($data, 200);
    }

    public function bookAuth() {
        $data = "Welcome " . Auth::user()->name;
        return response()->json($data, 200);
    }

}
