<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stevebauman\Location\Facades\Location;

class HomeController extends Controller
{
    public function getDetails(Request $request) {
        $ip=  $request->ip();
        $location = "New York";
        $greeting = "Hello, $request->visitor_name!, the temperature is 11 degrees Celcius in $location";

        return [
            'client_ip' => $ip,
            'location' => $location,
            'greeting' => $greeting
        ];
    }
}
