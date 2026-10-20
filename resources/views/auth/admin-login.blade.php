@extends('layouts.guest')
@section('title', 'Staff Portal — Sign In')
@section('content')
<div class="min-h-screen flex bg-zinc-950">

    {{-- Left panel — dark branding --}}
    <div class="hidden lg:flex lg:w-[480px] flex-col justify-between p-12 bg-zinc-900 border-r border-zinc-800">
        {{-- Logo --}}
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-indigo-600 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>
                </svg>
            </div>
            <span class="text-white font-semibold text-sm tracking-tight">RealEstate Platform</span>
        </div>

        {{-- Middle content --}}
        <div>
            <p class="text-xs font-semibold text-indigo-400 uppercase tracking-widest mb-4">Staff Portal</p>
            <h2 class="text-3xl font-bold text-white mb-4 leading-tight">Your agency's<br>command centre.</h2>
            <p class="text-zinc-400 text-sm leading-relaxed mb-8">Manage listings, handle inquiries, coordinate agents, and close deals — all from one place.</p>

            {{-- Feature list --}}
            <ul class="space-y-3">
                @foreach([
                    'Property listings & image management',
                    'Inquiry pipeline with Kanban view',
                    'Booking scheduling & conflict detection',
                    'Agent & client CRM',
                    'Real-time chat & notifications',
                ] as $feature)
                <li class="flex items-center gap-3 text-sm text-zinc-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 shrink-0"></span>
                    {{ $feature }}
                </li>
                @endforeach
            </ul>
        </div>

        {{-- Footer --}}
        <div>
            <p class="text-xs text-zinc-600">Looking for a property?
                <a href="{{ route('browse') }}" class="text-zinc-400 hover:text-zinc-200 transition-colors underline underline-offset-2">Browse listings</a>
                or
                <a href="{{ route('login') }}" class="text-zinc-400 hover:text-zinc-200 transition-colors underline underline-offset-2">client login</a>
            </p>
        </div>
    </div>

    {{-- Right panel — form --}}
    <div class="flex-1 flex flex-col justify-center items-center px-6 py-12">
        <div class="w-full max-w-sm">

            {{-- Mobile logo --}}
            <div class="flex items-center gap-2 mb-8 lg:hidden">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>
                    </svg>
                </div>
                <span class="text-white font-semibold text-sm">RealEstate Platform</span>
            </div>

            <p class="text-xs font-semibold text-indigo-400 uppercase tracking-widest mb-2">Staff Portal</p>
            <h1 class="text-2xl font-bold text-white mb-1">Sign in</h1>
            <p class="text-sm text-zinc-500 mb-8">Enter your agency credentials to continue</p>

            <form id="admin-login-form" class="space-y-4" autocomplete="off">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-medium text-zinc-400 mb-1.5">Email address</label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                           class="w-full px-3 py-2.5 text-sm bg-zinc-900 border border-zinc-700 rounded-lg text-white placeholder:text-zinc-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                           placeholder="you@agency.com">
                    <p class="field-error text-xs text-rose-400 mt-1 hidden" id="email-error"></p>
                </div>

                <div>
                    <label for="password" class="block text-xs font-medium text-zinc-400 mb-1.5">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required autocomplete="current-password"
                               class="w-full px-3 py-2.5 text-sm bg-zinc-900 border border-zinc-700 rounded-lg text-white placeholder:text-zinc-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow pr-10"
                               placeholder="••••••••">
                        <button type="button" id="toggle-pw"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-zinc-300 transition-colors"
                                aria-label="Show password">
                            <svg id="pw-eye" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                    </div>
                    <p class="field-error text-xs text-rose-400 mt-1 hidden" id="password-error"></p>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" id="remember" name="remember"
                           class="w-4 h-4 rounded border-zinc-700 bg-zinc-900 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-zinc-950">
                    <label for="remember" class="text-xs text-zinc-400">Keep me signed in for 30 days</label>
                </div>

                <button type="submit" id="login-btn"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-zinc-950 disabled:opacity-50 disabled:cursor-not-allowed mt-2">
                    <span id="login-btn-text">Sign in to staff portal</span>
                    <svg id="login-spinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </button>

                <p id="form-error" class="text-xs text-rose-400 text-center hidden"></p>
            </form>

            {{-- Divider --}}
            <div class="mt-8 pt-6 border-t border-zinc-800 text-center">
                <p class="text-xs text-zinc-600">
                    Not a staff member?
                    <a href="{{ route('login') }}" class="text-zinc-400 hover:text-zinc-200 transition-colors">
                        Client portal →
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(function() {
    // Toggle password visibility
    $('#toggle-pw').on('click', function() {
        var $input = $('#password');
        var isText = $input.attr('type') === 'text';
        $input.attr('type', isText ? 'password' : 'text');
        $(this).attr('aria-label', isText ? 'Show password' : 'Hide password');
        $('#pw-eye').html(isText
            ? '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>'
            : '<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>'
        );
    });

    // Form submit
    $('#admin-login-form').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#login-btn');
        $btn.prop('disabled', true);
        $('#login-btn-text').text('Signing in…');
        $('#login-spinner').removeClass('hidden');
        $('.field-error').addClass('hidden').text('');
        $('#form-error').addClass('hidden').text('');

        $.ajax({
            url: '/api/staff/login',
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: JSON.stringify({
                email:    $('#email').val(),
                password: $('#password').val(),
                remember: $('#remember').is(':checked')
            })
        }).done(function(res) {
            if (res.success) {
                // Staff should only end up at /super or / — not the client browse page
                var redirect = (res.data && res.data.redirect) ? res.data.redirect : '/';
                window.location.href = redirect;
            }
        }).fail(function(xhr) {
            var res = xhr.responseJSON || {};
            if (xhr.status === 422 && res.errors) {
                Object.keys(res.errors).forEach(function(field) {
                    var $err = $('#' + field + '-error');
                    if ($err.length) $err.text(Array.isArray(res.errors[field]) ? res.errors[field][0] : res.errors[field]).removeClass('hidden');
                });
            } else {
                $('#form-error').text(res.message || 'Sign in failed. Please try again.').removeClass('hidden');
            }
        }).always(function() {
            $btn.prop('disabled', false);
            $('#login-btn-text').text('Sign in to staff portal');
            $('#login-spinner').addClass('hidden');
        });
    });
});
</script>
@endpush
