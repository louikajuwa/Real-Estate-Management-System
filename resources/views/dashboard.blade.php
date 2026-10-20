@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div id="dashboard-page">
    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8" id="stat-cards">
        <div class="stat-card card p-6 animate-pulse">
            <div class="h-3 bg-zinc-100 rounded w-24 mb-4"></div>
            <div class="h-8 bg-zinc-100 rounded w-16 mb-3"></div>
            <div class="h-3 bg-zinc-100 rounded w-20"></div>
        </div>
        <div class="stat-card card p-6 animate-pulse">
            <div class="h-3 bg-zinc-100 rounded w-24 mb-4"></div>
            <div class="h-8 bg-zinc-100 rounded w-16 mb-3"></div>
            <div class="h-3 bg-zinc-100 rounded w-20"></div>
        </div>
        <div class="stat-card card p-6 animate-pulse">
            <div class="h-3 bg-zinc-100 rounded w-24 mb-4"></div>
            <div class="h-8 bg-zinc-100 rounded w-16 mb-3"></div>
            <div class="h-3 bg-zinc-100 rounded w-20"></div>
        </div>
        <div class="stat-card card p-6 animate-pulse">
            <div class="h-3 bg-zinc-100 rounded w-24 mb-4"></div>
            <div class="h-8 bg-zinc-100 rounded w-16 mb-3"></div>
            <div class="h-3 bg-zinc-100 rounded w-20"></div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 card p-6">
            <h3 class="text-sm font-semibold text-zinc-700 mb-4">Inquiry Pipeline</h3>
            <div class="relative h-48">
                <canvas id="chart-inquiry" role="img" aria-label="Inquiry pipeline chart"></canvas>
                <div id="chart-inquiry-skeleton" class="absolute inset-0 flex items-center justify-center">
                    <div class="w-full h-40 bg-zinc-50 rounded animate-pulse"></div>
                </div>
            </div>
        </div>
        <div class="card p-6">
            <h3 class="text-sm font-semibold text-zinc-700 mb-4">Properties</h3>
            <div class="relative h-48 flex items-center justify-center">
                <canvas id="chart-property" class="max-w-[180px]" role="img" aria-label="Properties by status chart"></canvas>
                <div id="chart-property-skeleton" class="absolute inset-0 flex items-center justify-center">
                    <div class="w-36 h-36 bg-zinc-50 rounded-full animate-pulse"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bookings Trend --}}
    <div class="card p-6 mb-8">
        <h3 class="text-sm font-semibold text-zinc-700 mb-4">Bookings (Last 30 Days)</h3>
        <div class="relative h-40">
            <canvas id="chart-bookings" role="img" aria-label="Bookings over the last 30 days chart"></canvas>
            <div id="chart-bookings-skeleton" class="absolute inset-0 flex items-center justify-center">
                <div class="w-full h-32 bg-zinc-50 rounded animate-pulse"></div>
            </div>
        </div>
    </div>

    {{-- Activity Feed --}}
    <div class="card p-6">
        <h3 class="text-sm font-semibold text-zinc-700 mb-4">Recent Activity</h3>
        <div id="activity-feed" class="space-y-3">
            @for($i=0;$i<5;$i++)
            <div class="flex items-start gap-3 animate-pulse">
                <div class="w-8 h-8 rounded-full bg-zinc-100 shrink-0"></div>
                <div class="flex-1 space-y-1">
                    <div class="h-3 bg-zinc-100 rounded w-3/4"></div>
                    <div class="h-3 bg-zinc-100 rounded w-1/3"></div>
                </div>
            </div>
            @endfor
        </div>
        <div id="activity-empty" class="hidden flex flex-col items-center justify-center py-8">
            <svg class="w-12 h-12 text-zinc-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-zinc-500 text-sm">No activity yet</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pages/dashboard.js') }}"></script>
@endpush
