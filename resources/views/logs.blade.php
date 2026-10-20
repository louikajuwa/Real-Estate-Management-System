@extends('layouts.app')
@section('title', 'Audit Logs')
@section('page-title', 'Audit Logs')

@section('content')
<div id="logs-page">
    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3 mb-6">
        <input id="log-search" type="search" placeholder="Search description or action…" aria-label="Search logs"
            class="input w-full sm:w-64">
        <input id="log-filter-from" type="date" aria-label="From date"
            class="input">
        <input id="log-filter-to" type="date" aria-label="To date"
            class="input">
        <button type="button" id="log-clear" class="btn btn-secondary">
            Clear
        </button>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 bg-zinc-50 text-left">
                        <th class="th">Time</th>
                        <th class="th">Actor</th>
                        <th class="th">Action</th>
                        <th class="th">Description</th>
                    </tr>
                </thead>
                <tbody id="log-tbody">
                    <tr class="log-skeleton">
                        <td colspan="4" class="px-4 py-6">
                            <div class="space-y-3">
                                @for($i=0;$i<5;$i++)
                                <div class="flex gap-4 animate-pulse">
                                    <div class="h-4 bg-zinc-100 rounded w-32"></div>
                                    <div class="h-4 bg-zinc-100 rounded w-28"></div>
                                    <div class="h-4 bg-zinc-100 rounded w-40"></div>
                                    <div class="h-4 bg-zinc-100 rounded flex-1"></div>
                                </div>
                                @endfor
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        {{-- Empty state --}}
        <div id="log-empty" class="hidden flex flex-col items-center justify-center py-20">
            <svg class="w-16 h-16 text-zinc-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-zinc-700 font-semibold text-sm">No activity yet</p>
            <p class="text-zinc-400 text-sm mt-1">Actions taken in the system will appear here.</p>
        </div>
        {{-- Pagination --}}
        <div id="log-pagination" class="hidden flex items-center justify-between px-4 py-3 border-t border-zinc-100">
            <span id="log-page-info" class="text-xs text-zinc-500"></span>
            <div id="log-page-btns" class="flex flex-wrap items-center gap-1" role="navigation" aria-label="Pagination"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/logs.js') }}"></script>
@endpush
