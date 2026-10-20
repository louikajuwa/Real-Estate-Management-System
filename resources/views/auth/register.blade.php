@extends('layouts.guest')
@section('title', 'Register')
@section('content')
<div class="min-h-screen flex items-center justify-center bg-zinc-50 px-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-sm border border-zinc-200 p-8">
            <h1 class="text-xl font-semibold text-zinc-900 mb-1">Create account</h1>
            <p class="text-sm text-zinc-500 mb-6">Register as a client to browse properties</p>

            <form id="register-form" class="space-y-4">
                @csrf
                <div>
                    <label for="name" class="label">Full name</label>
                    <input type="text" id="name" name="name" required class="input w-full" placeholder="Jane Smith">
                    <p class="text-xs text-rose-600 mt-1 hidden" id="name-error"></p>
                </div>
                <div>
                    <label for="email" class="label">Email address</label>
                    <input type="email" id="email" name="email" required class="input w-full" placeholder="you@example.com">
                    <p class="text-xs text-rose-600 mt-1 hidden" id="email-error"></p>
                </div>
                <div>
                    <label for="password" class="label">Password</label>
                    <input type="password" id="password" name="password" required class="input w-full" placeholder="••••••••">
                    <p class="text-xs text-rose-600 mt-1 hidden" id="password-error"></p>
                </div>
                <div>
                    <label for="password_confirmation" class="label">Confirm password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required class="input w-full" placeholder="••••••••">
                </div>
                <button type="submit" id="register-btn" class="btn btn-primary w-full py-2.5">
                    <span id="register-btn-text">Create account</span>
                    <svg id="register-spinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </button>
                <p id="form-error" class="text-xs text-rose-600 text-center hidden"></p>
                <p class="text-center text-xs text-zinc-500">Already have an account? <a href="/login" class="text-indigo-600 hover:underline">Sign in</a></p>
            </form>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(function() {
    $('#register-form').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#register-btn');
        $btn.prop('disabled', true);
        $('#register-btn-text').text('Creating account…');
        $('#register-spinner').removeClass('hidden');
        $('.field-error, [id$="-error"]').addClass('hidden').text('');
        $.ajax({
            url: '/api/register',
            method: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: JSON.stringify({ name: $('#name').val(), email: $('#email').val(), password: $('#password').val(), password_confirmation: $('#password_confirmation').val(), redirect: new URLSearchParams(window.location.search).get('redirect') || '' })
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
                $('#form-error').text(res.message || 'Registration failed.').removeClass('hidden');
            }
        }).always(function() {
            $btn.prop('disabled', false);
            $('#register-btn-text').text('Create account');
            $('#register-spinner').addClass('hidden');
        });
    });
});
</script>
@endpush
