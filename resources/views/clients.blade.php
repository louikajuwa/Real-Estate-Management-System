@extends('layouts.app')
@section('title', 'Clients')
@section('page-title', 'Clients')
@section('content')
<div id="clients-page">
    @if(auth()->user()->isAdmin())
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <select id="filter-agent" class="input input-sm" aria-label="Filter by agent">
            <option value="">All agents</option>
        </select>
    </div>
    @endif
    <div id="client-table-container"></div>
</div>

{{-- Client Modal --}}
<div id="client-modal" class="modal-backdrop hidden fixed inset-0 z-50 bg-zinc-900/50 items-center justify-center p-4">
    <div role="dialog" aria-modal="true" aria-labelledby="client-modal-title" class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-100">
            <h2 class="text-base font-semibold text-zinc-900" id="client-modal-title">Add Client</h2>
            <button type="button" data-modal-close class="btn-icon p-1" aria-label="Close">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="client-form" class="px-6 py-4 space-y-4">
            <input type="hidden" id="client-id">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="cf-name" class="label">Full name *</label>
                    <input type="text" id="cf-name" name="name" required class="input w-full" placeholder="John Smith">
                    <p class="text-xs text-rose-600 mt-1 hidden" id="cerr-name"></p>
                </div>
                <div>
                    <label for="cf-email" class="label">Email</label>
                    <input type="email" id="cf-email" name="email" class="input w-full" placeholder="john@example.com">
                </div>
                <div>
                    <label for="cf-phone" class="label">Phone</label>
                    <input type="text" id="cf-phone" name="phone" class="input w-full" placeholder="+1 555 000 0000">
                </div>
                <div class="sm:col-span-2">
                    <label for="cf-address" class="label">Address</label>
                    <input type="text" id="cf-address" name="address" class="input w-full" placeholder="123 Main St, City">
                </div>
                <div id="cf-agent-wrap" class="sm:col-span-2 @auth @if(!auth()->user()->isAdmin()) hidden @endif @endauth">
                    <label for="cf-agent" class="label">Assigned Agent</label>
                    <select id="cf-agent" name="assigned_agent_id" class="input w-full">
                        <option value="">None</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="cf-notes" class="label">Notes</label>
                    <textarea id="cf-notes" name="notes" rows="2" class="input w-full resize-none" placeholder="Internal notes..."></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-100">
                <button type="button" data-modal-close class="btn btn-secondary">Cancel</button>
                <button type="submit" id="client-save-btn" class="btn btn-primary">
                    <span id="client-save-text">Save</span>
                    <svg id="client-save-spin" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Interaction Timeline Modal --}}
<div id="timeline-modal" class="modal-backdrop hidden fixed inset-0 z-50 bg-zinc-900/50 items-center justify-center p-4">
    <div role="dialog" aria-modal="true" aria-labelledby="timeline-title" class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-100 sticky top-0 bg-white">
            <h2 class="text-base font-semibold text-zinc-900" id="timeline-title">Interaction History</h2>
            <button type="button" data-modal-close class="btn-icon p-1" aria-label="Close">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-4">
            {{-- Add interaction form --}}
            <div class="bg-zinc-50 rounded-xl p-4 mb-6">
                <p class="text-xs font-semibold text-zinc-700 mb-3" id="interaction-form-title">Log Interaction</p>
                <form id="interaction-form">
                    <input type="hidden" id="interaction-client-id">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="if-type" class="label">Type</label>
                            <select id="if-type" class="input input-sm w-full">
                                <option value="call">Call</option>
                                <option value="email">Email</option>
                                <option value="meeting">Meeting</option>
                                <option value="note">Note</option>
                            </select>
                        </div>
                        <div>
                            <label for="if-occurred-at" class="label">Date &amp; Time</label>
                            <input type="datetime-local" id="if-occurred-at" class="input input-sm w-full">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="if-notes" class="label">Notes</label>
                            <textarea id="if-notes" rows="2" class="input input-sm w-full resize-none" placeholder="What happened?"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end mt-2">
                        <button type="submit" id="interaction-save-btn" class="btn btn-primary btn-sm">Save</button>
                    </div>
                </form>
            </div>
            {{-- Timeline --}}
            <div id="interaction-timeline" class="space-y-3"></div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/pages/clients.js') }}"></script>
@endpush
