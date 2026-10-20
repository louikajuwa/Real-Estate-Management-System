@extends('layouts.app')
@section('title', 'Bookings')
@section('page-title', 'Bookings')

@section('content')
<div id="bookings-page">
    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <select id="bk-filter-status" class="input" aria-label="Filter by status">
                <option value="">All Statuses</option>
                <option value="scheduled">Scheduled</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <input id="bk-filter-from" type="date" aria-label="From date"
                class="input">
            <input id="bk-filter-to" type="date" aria-label="To date"
                class="input">
        </div>
        <button type="button" id="btn-new-booking" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Booking
        </button>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div id="bk-table-wrap" class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 bg-zinc-50 text-left">
                        <th class="th">Property</th>
                        <th class="th">Client</th>
                        <th class="th">Agent</th>
                        <th class="th">Scheduled At</th>
                        <th class="th">Duration</th>
                        <th class="th">Status</th>
                        <th class="th">Actions</th>
                    </tr>
                </thead>
                <tbody id="bk-tbody">
                    <tr class="bk-skeleton">
                        <td colspan="7" class="px-4 py-8 text-center text-zinc-400 text-sm">
                            <div class="flex flex-col items-center gap-2">
                                @for($i=0;$i<3;$i++)
                                <div class="w-full flex gap-4 animate-pulse">
                                    <div class="h-4 bg-zinc-100 rounded flex-1"></div>
                                    <div class="h-4 bg-zinc-100 rounded flex-1"></div>
                                    <div class="h-4 bg-zinc-100 rounded flex-1"></div>
                                    <div class="h-4 bg-zinc-100 rounded w-24"></div>
                                    <div class="h-4 bg-zinc-100 rounded w-16"></div>
                                    <div class="h-4 bg-zinc-100 rounded w-20"></div>
                                    <div class="h-4 bg-zinc-100 rounded w-16"></div>
                                </div>
                                @endfor
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        {{-- Empty state --}}
        <div id="bk-empty" class="hidden flex flex-col items-center justify-center py-20">
            <svg class="w-16 h-16 text-zinc-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-zinc-700 font-semibold text-sm">No bookings found</p>
            <p class="text-zinc-400 text-sm mt-1">Schedule a viewing to get started.</p>
            <button type="button" id="bk-empty-new" class="btn btn-primary mt-4">
                Schedule Booking
            </button>
        </div>
        {{-- Pagination --}}
        <div id="bk-pagination" class="hidden flex items-center justify-between px-4 py-3 border-t border-zinc-100">
            <span id="bk-page-info" class="text-xs text-zinc-500"></span>
            <div id="bk-page-btns" class="flex flex-wrap items-center gap-1" role="navigation" aria-label="Pagination"></div>
        </div>
    </div>
</div>

{{-- New Booking Modal --}}
<div id="modal-booking" class="modal-backdrop hidden fixed inset-0 z-50 bg-zinc-900/50 items-center justify-center p-4">
    <div role="dialog" aria-modal="true" aria-labelledby="modal-booking-title" class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 id="modal-booking-title" class="text-base font-semibold text-zinc-900">New Booking</h3>
            <button type="button" data-modal-close class="btn-icon p-1" aria-label="Close">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="form-booking" novalidate>
            <input type="hidden" name="_booking_id">
            <div class="space-y-4">
                <div>
                    <label for="bk-f-property-id" class="label">Property *</label>
                    <select id="bk-f-property-id" name="property_id"
                        class="input w-full">
                        <option value="">Select a property...</option>
                    </select>
                    <p class="field-error field-error-text hidden" role="alert"></p>
                </div>
                <div id="bk-client-row">
                    <label for="bk-f-client-id" class="label">Client</label>
                    <select id="bk-f-client-id" name="client_id"
                        class="input w-full">
                        <option value="">Select a client...</option>
                    </select>
                    <p class="field-error field-error-text hidden" role="alert"></p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="bk-f-scheduled-at" class="label">Date &amp; Time *</label>
                        <input id="bk-f-scheduled-at" type="datetime-local" name="scheduled_at"
                            class="input w-full">
                        <p class="field-error field-error-text hidden" role="alert"></p>
                    </div>
                    <div>
                        <label for="bk-f-duration-min" class="label">Duration (min)</label>
                        <input id="bk-f-duration-min" type="number" name="duration_min" value="60" min="15" max="480"
                            class="input w-full">
                        <p class="field-error field-error-text hidden" role="alert"></p>
                    </div>
                </div>
                <div>
                    <label for="bk-f-notes" class="label">Notes</label>
                    <textarea id="bk-f-notes" name="notes" rows="3"
                        class="input w-full resize-none"
                        placeholder="Optional notes..."></textarea>
                    <p class="field-error field-error-text hidden" role="alert"></p>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" data-modal-close class="btn btn-secondary">Cancel</button>
                <button type="submit" id="btn-bk-submit" class="btn btn-primary">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Status Change Modal --}}
<div id="modal-bk-status" class="modal-backdrop hidden fixed inset-0 z-50 bg-zinc-900/50 items-center justify-center p-4">
    <div role="dialog" aria-modal="true" aria-labelledby="bk-status-title" class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6 max-h-[90vh] overflow-y-auto">
        <h3 id="bk-status-title" class="text-base font-semibold text-zinc-900 mb-4">Update Status</h3>
        <p id="bk-status-context" class="text-sm text-zinc-600 mb-4"></p>
        <input type="hidden" id="bk-status-booking-id">
        <div class="flex flex-col gap-2">
            <button type="button" data-status="scheduled"
                class="btn bg-indigo-50 text-indigo-700 hover:bg-indigo-100">Scheduled</button>
            <button type="button" data-status="completed"
                class="btn bg-emerald-50 text-emerald-700 hover:bg-emerald-100">Completed</button>
            <button type="button" data-status="cancelled"
                class="btn bg-rose-50 text-rose-700 hover:bg-rose-100">Cancelled</button>
        </div>
        <button type="button" data-modal-close class="btn btn-secondary mt-4 w-full">Cancel</button>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/bookings.js') }}"></script>
@endpush
