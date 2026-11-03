<?php

namespace App\Http\Controllers;

use App\Disaster;
use App\Information;
use App\Support\ShelterStatusSummary;
use Illuminate\Support\Facades\Auth;

class RootController extends Controller
{
    public function __invoke()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        $disasters = Disaster::with('localGovernments.prefecture')
            ->where('status', true)
            ->orderBy('event_date', 'DESC')
            ->limit(5)
            ->get();

        $information = Information::query()
            ->conditionForFront()
            ->orderBy('published_at', 'DESC')
            ->limit(5)
            ->get();

        $signalCounts = ShelterStatusSummary::today();

        return view('public.index', compact('disasters', 'information', 'signalCounts'));
    }
}
