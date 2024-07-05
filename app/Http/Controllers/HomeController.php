<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class HomeController extends Controller
{
    public function getDetails(Request $request) {

try {
    // Get client's IP address, considering various headers
    $clientIp = $request->header('X-Forwarded-For') ?: $request->ip();

    // Get latitude and longitude using Google Geolocation API
    $googleApiKey = env('GOOGLE_API_KEY');
    $client = new Client();
    $response = $client->post('https://www.googleapis.com/geolocation/v1/geolocate', [
        'query' => [
            'key' => $googleApiKey,
        ],
        'json' => [
            'considerIp' => true,
        ]
    ]);
    $data = json_decode($response->getBody(), true);

    if (isset($data['location'])) {
        $lat = $data['location']['lat'];
        $lng = $data['location']['lng'];
    } else {
        throw new \Exception('Failed to get location from Google Geolocation API.');
    }

    // Optionally, get location details using Google Geocoding API
    $response = $client->get('https://maps.googleapis.com/maps/api/geocode/json', [
        'query' => [
            'latlng' => "{$lat},{$lng}",
            'key' => $googleApiKey,
        ]
    ]);

    // Initialize variable to store location
    $location = 'Unknown location';

    // Process address components to find the location
    $geoData = json_decode($response->getBody(), true);
    foreach ($geoData['results'] as $result) {
        foreach ($result['address_components'] as $component) {
            if (in_array('administrative_area_level_1', $component['types'])) {
                $location = $component['long_name'];
                break 2; // Exit both loops
            }
        }
    }

    // Get weather data using OpenWeatherMap API
    $openWeatherApiKey = env('OPENWEATHER_API_KEY');
    $response = $client->get('https://api.openweathermap.org/data/2.5/weather', [
        'query' => [
            'lat' => $lat,
            'lon' => $lng,
            'appid' => $openWeatherApiKey,
            'units' => 'metric' // For temperature in Celsius
        ]
    ]);
    $weatherData = json_decode($response->getBody(), true);
    $temperature = $weatherData['main']['temp'];

    return response()->json([
        'client_ip' => $clientIp,
        'location' => $location,
        'greeting' => "Hello, {$request->visitor_name}! The temperature is {$temperature} degrees Celsius in {$location}."
    ]);
} catch (\Exception $e) {
    Log::error('Error getting weather data: ' . $e->getMessage());
    return response()->json(['error' => 'Internal Server Error'], 500);
}
    }
}
