<?php
namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WeatherController extends Controller
{
    protected $weather;

    public function __construct(WeatherService $weather)
    {
        $this->weather = $weather;
    }

    public function show(Request $request)
    {
        $city = $request->input('city', 'Madrid');
        $weather = $this->weather->getCurrentWeather($city);

        return Inertia::render('Weather', [
            'weather' => $weather,
            'city' => $city
        ]);
    }
}

