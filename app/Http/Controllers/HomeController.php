<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class HomeController extends Controller
{
    public function getDetails(Request $request) {

        try {
            $apiKey = env('IPGEOLOCATION_API_KEY');
            $ip = $request->header('X-Forwarded-For') ?? $request->header('X-Real-IP') ?? $request->ip();
            $location = $this->get_geolocation($apiKey, $ip);
            $decodedLocation = json_decode($location, true);

            // Get latitude, city and longitude
            $lat = $decodedLocation['latitude'];
            $lon = $decodedLocation['longitude'];
            $location = $decodedLocation['city'];

            // Get weather data using OpenWeatherMap API
            $openWeatherApiKey = env('OPENWEATHER_API_KEY');
            $client = new Client();
            $response = $client->get('https://api.openweathermap.org/data/2.5/weather', [
                'query' => [
                    'lat' => $lat,
                    'lon' => $lon,
                    'appid' => $openWeatherApiKey,
                    'units' => 'metric' // For temperature in Celsius
                ]
            ]);
            $weatherData = json_decode($response->getBody(), true);
            $temperature = $weatherData['main']['temp'];

            return response()->json([
                'client_ip' => $ip,
                'location' => $location,
                'greeting' => "Hello, {$request->visitor_name}! The temperature is {$temperature} degrees Celsius in {$location}."
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting weather data: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function get_geolocation($apiKey, $ip, $lang = "en", $fields = "*", $excludes = "") {
        $url = "https://api.ipgeolocation.io/ipgeo?apiKey=".$apiKey."&ip=".$ip."&lang=".$lang."&fields=".$fields."&excludes=".$excludes;
        $cURL = curl_init();

        curl_setopt($cURL, CURLOPT_URL, $url);
        curl_setopt($cURL, CURLOPT_HTTPGET, true);
        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: '.$_SERVER['HTTP_USER_AGENT']
        ));

        return curl_exec($cURL);
    }
}
