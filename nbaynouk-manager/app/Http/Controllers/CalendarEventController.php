<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalendarEventRequest;
use App\Http\Requests\UpdateCalendarEventRequest;
use App\Models\CalendarEvent;
use Illuminate\Http\JsonResponse;

class CalendarEventController extends Controller
{
    public function store(StoreCalendarEventRequest $request): JsonResponse
    {
        $event = CalendarEvent::create([...$request->validated(), 'created_by' => $request->user()->id]);

        return response()->json(['success' => true, 'message' => 'Événement ajouté au calendrier.', 'event' => $this->payload($event)], 201);
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $calendarEvent): JsonResponse
    {
        $calendarEvent->update($request->validated());

        return response()->json(['success' => true, 'message' => 'Événement mis à jour.', 'event' => $this->payload($calendarEvent->fresh())]);
    }

    public function destroy(CalendarEvent $calendarEvent): JsonResponse
    {
        $calendarEvent->delete();

        return response()->json(['success' => true, 'message' => 'Événement supprimé.']);
    }

    private function payload(CalendarEvent $event): array
    {
        return ['id' => $event->id, 'title' => $event->title, 'date' => $event->event_date->toDateString(), 'color' => $event->color->value, 'color_label' => $event->color->label(), 'notes' => $event->notes];
    }
}
