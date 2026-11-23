<?php

namespace App\Http\Controllers;

use App\Disaster;
use App\Information;
use App\Services\DisasterMapContext;
use App\Support\ShelterStatusSummary;
use Illuminate\Support\Facades\Auth;

class RootController extends Controller
{
    public function __invoke(DisasterMapContext $mapContext)
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        $disaster = Disaster::query()
            ->where('status', true)
            ->whereHas('localGovernments', function ($query) {
                $query->whereNotNull('code')->where('code', '!=', '');
            })
            ->orderBy('event_date', 'DESC')
            ->first();

        $information = Information::query()
            ->conditionForFront()
            ->orderBy('published_at', 'DESC')
            ->limit(5)
            ->get();

        if (!$disaster) {
            $signalCounts = config('public.show_support_status', false)
                ? ShelterStatusSummary::today()
                : [];

            return view('public.index', [
                'disastersData' => collect(),
                'information' => $information,
                'signalCounts' => $signalCounts,
            ]);
        }

        $data = $mapContext->build($disaster);
        $data['disasterOptions'] = $mapContext->options();
        $data['topInformation'] = $information;

        return view('disasters.show', $data);
    }
}
