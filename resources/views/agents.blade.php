@extends('layouts.app')
@section('title', 'Agents')
@section('page-title', 'Agents')
@section('content')
<div id="agents-page">
    <div id="agent-table-container"></div>
</div>

{{-- Agent Modal --}}
<div id="agent-modal" class="modal-backdrop hidden fixed inset-0 z-50 bg-zinc-900/50 items-center justify-center p-4">
    <div role="dialog" aria-modal="true" aria-labelledby="agent-modal-title" class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-100">
            <h2 class="text-base font-semibold text-zinc-900" id="agent-modal-title">Add Agent</h2>
            <button type="button" data-modal-close class="btn-icon p-1" aria-label="Close">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="agent-form" class="px-6 py-4 space-y-4">
            <input type="hidden" id="agent-id">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="af-name" class="label">Full name *</label>
                    <input type="text" id="af-name" name="name" required class="input w-full" placeholder="Sara Johnson">
                    <p class="text-xs text-rose-600 mt-1 hidden" id="aerr-name"></p>
                </div>
                <div>
                    <label for="af-email" class="label">Email *</label>
                    <input type="email" id="af-email" name="email" required class="input w-full">
                    <p class="text-xs text-rose-600 mt-1 hidden" id="aerr-email"></p>
                </div>
                <div>
                    <label for="af-phone" class="label">Phone</label>
                    <input type="text" id="af-phone" name="phone" class="input w-full" placeholder="+1 555 000 0000">
                </div>
                <div>
                    <label for="af-password" class="label">Password <span id="pwd-hint" class="text-zinc-400 font-normal">(required)</span></label>
                    <input type="password" id="af-password" name="password" class="input w-full" placeholder="min 8 characters">
                    <p class="text-xs text-rose-600 mt-1 hidden" id="aerr-password"></p>
                </div>
                <div>
                    <label for="af-license" class="label">License #</label>
                    <input type="text" id="af-license" name="license_no" class="input w-full" placeholder="RE-12345">
                </div>
                <div class="sm:col-span-2">
                    <label for="af-bio" class="label">Bio</label>
                    <textarea id="af-bio" name="bio" rows="2" class="input w-full resize-none" placeholder="Short bio…"></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-100">
                <button type="button" data-modal-close class="btn btn-secondary">Cancel</button>
                <button type="submit" id="agent-save-btn" class="btn btn-primary">
                    <span id="agent-save-text">Save</span>
                    <svg id="agent-save-spin" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/pages/agents.js') }}"></script>
@endpush
