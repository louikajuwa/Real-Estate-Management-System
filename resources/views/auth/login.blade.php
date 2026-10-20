@extends('layouts.guest')
@section('title', 'Sign In')
@section('content')
<div class="min-h-screen flex">
    {{-- Left panel --}}
    <div class="hidden lg:flex lg:flex-1 bg-gradient-to-br from-indigo-500 to-indigo-700 items-center justify-center p-12 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle at 20% 50%,white 1px,transparent 1px),radial-gradient(circle at 80% 20%,white 1px,transparent 1px),radial-gradient(circle at 60% 80%,white 1px,transparent 1px);background-size:60px 60px;"></div>
        <div class="max-w-sm text-white relative z-10">
            <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center mb-6">
                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>
                </svg>
            </div>
            <h2 class="text-3xl font-bold mb-3">Find your next home</h2>
            <p class="text-indigo-200 text-sm leading-relaxed mb-8">Browse properties from top agencies across the UAE. Sign in to inquire, schedule viewings, and more.</p>
            <a href="{{ route('browse') }}" class="inline-flex items-center gap-2 text-sm font-medium text-white/80 hover:text-white transition-colors">
                <span>Browse without signing in</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
    </div>
    {{-- Right panel --}}
    <div class="flex-1 flex flex-col justify-center px-6 py-12 lg:px-12 max-w-md mx-auto w-full lg:max-w-none lg:flex-none lg:w-96">
        <div>
            <p class="text-xs font-medium text-indigo-600 uppercase tracking-widest mb-2">Client Portal</p>
            <h1 class="text-2xl font-bold text-zinc-900 mb-1">Welcome back</h1>
            <p class="text-sm text-zinc-500 mb-8">Sign in to manage your inquiries and bookings</p>

            <form id="login-form" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="label">Email address</label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                           class="input w-full"
                           placeholder="you@example.com">
                    <p class="field-error text-xs text-rose-600 mt-1 hidden" id="email-error"></p>
                </div>
                <div>
                    <label for="password" class="label">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                           class="input w-full"
                           placeholder="••••••••">
                    <p class="field-error text-xs text-rose-600 mt-1 hidden" id="password-error"></p>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="remember" class="text-xs text-zinc-600">Keep me signed in</label>
                </div>
                <button type="submit" id="login-btn"
                        class="btn btn-primary w-full py-2.5">
                    <span id="login-btn-text">Sign in</span>
                    <svg id="login-spinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </button>
                <p id="form-error" class="text-xs text-rose-600 text-center hidden"></p>
            </form>

            <div class="mt-5 space-y-3 text-center">
                <p class="text-xs text-zinc-500">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">Register free</a>
                </p>
                <div class="border-t border-zinc-100 pt-3">
                    <a href="{{ route('admin.login') }}"
                       class="inline-flex items-center gap-1 text-xs text-zinc-400 hover:text-zinc-600 transition-colors">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                        Agent or Admin? Staff portal →
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(function() {
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#login-btn');
        $btn.prop('disabled', true);
        $('#login-btn-text').text('Signing in…');
        $('#login-spinner').removeClass('hidden');
        $('.field-error').addClass('hidden').text('');
        $('#form-error').addClass('hidden').text('');

        $.ajax({
            url: '/api/login',
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: JSON.stringify({
                email: $('#email').val(),
                password: $('#password').val(),
                remember: $('#remember').is(':checked')
            })
        }).done(function(res) {
            if (res.success) {
                window.location.href = (res.data && res.data.redirect) ? res.data.redirect : '/';
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
            $('#login-btn-text').text('Sign in');
            $('#login-spinner').addClass('hidden');
        });
    });
});
</script>
@endpush
