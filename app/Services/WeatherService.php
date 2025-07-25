<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WeatherService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.weatherapi.key');
        $this->baseUrl = 'http://api.weatherapi.com/v1';
    }

    public function getCurrentWeather($city)
    {
        $response = Http::get("{$this->baseUrl}/current.json", [
            'key' => $this->apiKey,
            'q' => $city,
            'aqi' => 'no'
        ]);

        return $response->json();
    }
}
