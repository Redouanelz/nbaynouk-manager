<?php

namespace App\Http\Controllers;

use App\Enums\CalendarEventColor;
use App\Models\CalendarEvent;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function __invoke(Request $request): View
    {
        $value = (string) $request->query('month', '');
        try {
            $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)
                ? CarbonImmutable::createFromFormat('!Y-m', $value, config('app.timezone'))
                : CarbonImmutable::now(config('app.timezone'))->startOfMonth();
        } catch (\Throwable) {
            $month = CarbonImmutable::now(config('app.timezone'))->startOfMonth();
        }

        $gridStart = $month->startOfWeek(CarbonImmutable::MONDAY);
        $gridEnd = $month->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);
        $events = CalendarEvent::query()
            ->whereBetween('event_date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->orderBy('event_date')->orderBy('id')->get()->groupBy(fn (CalendarEvent $event) => $event->event_date->toDateString());

        $days = collect();
        for ($day = $gridStart; $day->lte($gridEnd); $day = $day->addDay()) {
            $days->push($day);
        }

        return view('calendar.index', [
            'month' => $month, 'days' => $days, 'eventsByDate' => $events,
            'previousMonth' => $month->subMonth()->format('Y-m'), 'nextMonth' => $month->addMonth()->format('Y-m'),
            'todayMonth' => CarbonImmutable::now(config('app.timezone'))->format('Y-m'),
            'colors' => CalendarEventColor::cases(),
        ]);
    }
}
