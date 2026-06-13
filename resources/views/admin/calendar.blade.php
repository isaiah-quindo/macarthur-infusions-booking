@extends('layouts.admin')

@section('title', 'Calendar')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-brand-teal">Calendar</h1>
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-brand-muted">
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block size-3 rounded-sm bg-brand-green"></span>
                Confirmed
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block size-3 rounded-sm bg-brand-orange"></span>
                Awaiting payment
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block size-3 rounded-sm bg-brand-blue"></span>
                Completed
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block size-3 rounded-sm bg-red-700"></span>
                Cancelled
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block size-3 rounded-sm bg-brand-muted"></span>
                No-show
            </span>
        </div>
    </div>

    <x-ui.card>
        <div id="calendar"></div>
    </x-ui.card>
</div>

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<style>
    /* Map FullCalendar's chrome onto the Macarthur brand tokens. */
    #calendar { font-family: inherit; }
    #calendar .fc-toolbar-title {
        font-family: var(--font-display);
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--color-brand-teal);
    }
    #calendar .fc-button {
        background: white;
        border: 1px solid var(--color-brand-border);
        color: var(--color-brand-teal);
        text-transform: capitalize;
        font-weight: 500;
        box-shadow: none;
        padding: 0.375rem 0.875rem;
    }
    #calendar .fc-button:hover { background: var(--color-brand-mist); border-color: var(--color-brand-border); }
    #calendar .fc-button:focus { box-shadow: 0 0 0 3px color-mix(in oklab, var(--color-brand-green) 25%, transparent); }
    #calendar .fc-button-primary:not(:disabled).fc-button-active,
    #calendar .fc-button-primary:not(:disabled):active {
        background: var(--color-brand-teal);
        border-color: var(--color-brand-teal);
        color: white;
    }
    #calendar .fc-today-button { display: none; }

    /* Grid */
    #calendar .fc-col-header-cell-cushion,
    #calendar .fc-daygrid-day-number {
        color: var(--color-brand-muted);
        font-weight: 500;
        text-decoration: none;
    }
    #calendar .fc-day-today { background: color-mix(in oklab, var(--color-brand-mist) 70%, transparent) !important; }
    #calendar .fc-scrollgrid,
    #calendar .fc-scrollgrid td,
    #calendar .fc-scrollgrid th { border-color: var(--color-brand-border); }

    /* Events */
    #calendar .fc-event { cursor: pointer; padding: 2px 5px; font-size: 0.8125rem; border-radius: 4px; }
    #calendar .fc-event-title { font-weight: 500; }
    #calendar .fc-h-event .fc-event-main { color: white; }

    /* Month view: show full time, truncate the name/service. */
    #calendar .fc-daygrid-event .fc-event-main { display: flex; align-items: baseline; gap: 4px; min-width: 0; }
    #calendar .fc-daygrid-event .fc-event-time { font-weight: 600; flex-shrink: 0; white-space: nowrap; }
    #calendar .fc-daygrid-event .fc-event-title {
        flex: 1 1 auto;
        min-width: 0;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    /* Now indicator — use brand orange instead of FC's default red */
    #calendar .fc-timegrid-now-indicator-line,
    #calendar .fc-timegrid-now-indicator-arrow { border-color: var(--color-brand-orange); }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        timeZone: @js(config('booking.clinic_timezone')),
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        firstDay: 1, // Monday
        nowIndicator: true,
        slotMinTime: '07:00:00',
        slotMaxTime: '20:00:00',
        slotDuration: '00:15:00',
        expandRows: true,
        height: 'auto',
        eventDisplay: 'block',
        eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
        // FullCalendar passes ?start=...&end=... ISO strings for the visible range.
        events: @js(route('admin.calendar.events')),
        eventClick(info) {
            // Use FullCalendar's `url` prop with same-tab navigation.
            info.jsEvent.preventDefault();
            if (info.event.url) window.location.href = info.event.url;
        },
        eventDidMount(info) {
            const ref = info.event.extendedProps.reference;
            const status = info.event.extendedProps.statusLabel;
            if (ref) info.el.setAttribute('title', `${info.event.title} • ${ref} • ${status}`);
        },
    });
    calendar.render();
});
</script>
@endpush
@endsection
