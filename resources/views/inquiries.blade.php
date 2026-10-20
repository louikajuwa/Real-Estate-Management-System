@extends('layouts.app')
@section('title', 'Inquiries')
@section('page-title', 'Inquiries')

@section('content')
<div id="inquiries-page">
    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <input id="inq-search" type="search" placeholder="Search inquiries…" aria-label="Search inquiries"
                class="input w-full sm:w-56">
            <select id="inq-filter-property" class="input" aria-label="Filter by property">
                <option value="">All Properties</option>
            </select>
        </div>
        @auth
        @if(auth()->user()->hasAnyRole(['admin','agent']))
        <button type="button" id="btn-new-inquiry" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Inquiry
        </button>
        @endif
        @endauth
    </div>

    {{-- Kanban Board --}}
    <div class="flex gap-4 overflow-x-auto pb-4" id="kanban-board" role="region" aria-label="Inquiry pipeline (scrolls horizontally on small screens)" tabindex="0">
        @foreach([
            ['key'=>'new','label'=>'New','color'=>'indigo'],
            ['key'=>'contacted','label'=>'Contacted','color'=>'blue'],
            ['key'=>'viewing_scheduled','label'=>'Viewing Scheduled','color'=>'amber'],
            ['key'=>'negotiation','label'=>'Negotiation','color'=>'orange'],
            ['key'=>'closed','label'=>'Closed','color'=>'zinc'],
        ] as $col)
        <div class="kanban-col shrink-0 w-64 flex flex-col"
             data-status="{{ $col['key'] }}">
            <div class="flex items-center justify-between mb-3 px-1">
                <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $col['label'] }}</span>
                <span class="kanban-count text-xs font-medium bg-zinc-100 text-zinc-600 rounded-full px-2 py-0.5">0</span>
            </div>
            <div class="kanban-lane min-h-[200px] flex flex-col gap-2 rounded-xl bg-zinc-50 p-2"
                 data-status="{{ $col['key'] }}">
                {{-- Skeleton placeholders --}}
                <div class="skeleton-card animate-pulse space-y-2">
                    <div class="h-4 bg-zinc-200 rounded w-3/4"></div>
                    <div class="h-3 bg-zinc-200 rounded w-1/2"></div>
                    <div class="h-3 bg-zinc-200 rounded w-1/3"></div>
                </div>
                <div class="skeleton-card animate-pulse space-y-2">
                    <div class="h-4 bg-zinc-200 rounded w-2/3"></div>
                    <div class="h-3 bg-zinc-200 rounded w-1/2"></div>
                    <div class="h-3 bg-zinc-200 rounded w-1/4"></div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Empty state (hidden until loaded with no data) --}}
    <div id="kanban-empty" class="hidden flex flex-col items-center justify-center py-20">
        <svg class="w-16 h-16 text-zinc-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4z"/>
        </svg>
        <p class="text-zinc-700 font-semibold text-sm">No inquiries yet</p>
        <p class="text-zinc-400 text-sm mt-1">Inquiries from clients will appear here.</p>
    </div>
</div>

{{-- New Inquiry Modal --}}
<div id="modal-inquiry" class="modal-backdrop hidden fixed inset-0 z-50 bg-zinc-900/50 items-center justify-center p-4">
    <div role="dialog" aria-modal="true" aria-labelledby="modal-inquiry-title" class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 id="modal-inquiry-title" class="text-base font-semibold text-zinc-900">New Inquiry</h3>
            <button type="button" data-modal-close class="btn-icon p-1" aria-label="Close">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="form-inquiry" novalidate>
            <div class="space-y-4">
                <div>
                    <label for="inq-property-select" class="label">Property *</label>
                    <select name="property_id" id="inq-property-select"
                        class="input w-full">
                        <option value="">Select a property…</option>
                    </select>
                    <p class="field-error field-error-text hidden" role="alert"></p>
                </div>
                <div id="inq-client-row">
                    <label for="inq-client-select" class="label">Client</label>
                    <select name="client_id" id="inq-client-select"
                        class="input w-full">
                        <option value="">Select a client…</option>
                    </select>
                    <p class="field-error text-xs text-rose-600 mt-1 hidden"></p>
                </div>
                <div>
                    <label for="inq-f-message" class="label">Message *</label>
                    <textarea id="inq-f-message" name="message" rows="4" maxlength="2000"
                        class="input w-full resize-none"
                        placeholder="Describe the inquiry…"></textarea>
                    <p class="field-error text-xs text-rose-600 mt-1 hidden"></p>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" data-modal-close class="btn btn-secondary">Cancel</button>
                <button type="submit" id="btn-inq-submit" class="btn btn-primary">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Inquiry Detail Modal --}}
<div id="modal-inq-detail" class="modal-backdrop hidden fixed inset-0 z-50 bg-zinc-900/50 items-center justify-center p-4">
    <div role="dialog" aria-modal="true" aria-labelledby="modal-inq-detail-title" class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 id="modal-inq-detail-title" class="text-base font-semibold text-zinc-900">Inquiry Details</h3>
            <button type="button" data-modal-close class="btn-icon p-1" aria-label="Close">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="inq-detail-body" class="space-y-3 text-sm text-zinc-700"></div>
        <div id="inq-status-row" class="mt-4 hidden">
            <label for="inq-status-select" class="label">Move to stage</label>
            <select id="inq-status-select" class="input w-full">
                <option value="new">New</option>
                <option value="contacted">Contacted</option>
                <option value="viewing_scheduled">Viewing Scheduled</option>
                <option value="negotiation">Negotiation</option>
                <option value="closed">Closed</option>
            </select>
        </div>
        <div class="flex flex-wrap justify-end gap-3 mt-5">
            <button type="button" id="btn-inq-delete" class="btn btn-danger-outline hidden">Delete</button>
            <button type="button" data-modal-close class="btn btn-secondary">Close</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/inquiries.js') }}"></script>
@endpush
