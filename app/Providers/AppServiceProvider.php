<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\CostCenter;
use App\Models\Variety;
use App\Models\Fruit;
use App\Models\CompanyReason;
use App\Models\Season;
use App\Models\Parcel;
use App\Models\Level2;
use App\Models\Level3;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Inertia::share([
            'hasCostCenter' => function () {
                if (!Auth::check()) {
                    return false;
                }
                $seasonId = session('season_id');
                $teamId = Auth::user()->team_id;
                return $seasonId
                    ? CostCenter::where('season_id', $seasonId)
                        ->whereHas('season', function ($query) use ($teamId) {
                            $query->where('team_id', $teamId);
                        })
                        ->exists()
                    : false;
            },
            'hasVariety' => fn() => Auth::check() && Variety::where('team_id', Auth::user()->team_id)->exists(),
            'hasFruit' => fn() => Auth::check() && Fruit::where('team_id', Auth::user()->team_id)->exists(),
            'hasCompanyReason' => fn() => Auth::check() && CompanyReason::where('team_id', Auth::user()->team_id)->exists(),
            'hasSeason' => fn() => Auth::check() && Season::where('team_id', Auth::user()->team_id)->exists(),
            'hasParcel' => fn() => Auth::check() && Parcel::where('team_id', Auth::user()->team_id)->exists(),
            'hasLevel3' => function () {
                if (!Auth::check()) return false;
                $teamId = Auth::user()->team_id;
                $level2Ids = Level2::whereIn('level1_id', function ($query) use ($teamId) {
                    $query->select('id')
                        ->from('level1s')
                        ->where('team_id', $teamId);
                })->pluck('id');
                return Level3::whereIn('level2_id', $level2Ids)->exists();
            },
        ]);
    }
}
