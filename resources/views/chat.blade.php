@extends('layouts.app')
@section('title', 'Chat')
@section('page-title', 'Chat')

@section('content')
<div id="chat-page" class="flex h-[calc(100dvh-9rem)] min-h-[24rem] gap-0 card overflow-hidden">
    {{-- Contacts Panel --}}
    <div id="contacts-panel" class="w-full md:w-64 lg:w-72 shrink-0 flex flex-col border-r border-zinc-100">
        <div class="px-4 py-3 border-b border-zinc-100">
            <h3 class="text-sm font-semibold text-zinc-900">Messages</h3>
        </div>
        <div id="contact-list" class="flex-1 overflow-y-auto"></div>
    </div>

    {{-- Thread Panel --}}
    <div id="thread-panel" class="hidden md:flex flex-col flex-1 min-w-0">
        {{-- Thread header (hidden until contact selected) --}}
        <div id="chat-thread-panel" class="hidden flex items-center gap-3 px-4 py-3 border-b border-zinc-100">
            <button type="button" id="btn-back-contacts" class="btn-icon md:hidden text-zinc-500 mr-1" aria-label="Back to conversations">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-semibold">
                <span id="thread-initial">—</span>
            </div>
            <span id="thread-name" class="text-sm font-semibold text-zinc-900">—</span>
        </div>

        {{-- Empty thread state --}}
        <div id="thread-empty" class="flex flex-col items-center justify-center flex-1 py-12">
            <svg class="w-14 h-14 text-zinc-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
            </svg>
            <p class="text-zinc-700 font-semibold text-sm">Select a conversation</p>
            <p class="text-zinc-400 text-sm mt-1">Choose a contact from the left to start chatting.</p>
        </div>

        {{-- Messages thread --}}
        <div id="chat-thread" class="flex-1 overflow-y-auto px-4 py-4 space-y-1 relative" role="log" aria-live="polite" aria-label="Conversation">
            {{-- New message pill --}}
            <div id="chat-new-pill" class="hidden sticky bottom-2 flex justify-center">
                <button type="button" class="bg-indigo-600 text-white rounded-full px-3 py-1 text-xs font-medium shadow-md">
                    ↓ New message
                </button>
            </div>
        </div>

        {{-- Input area --}}
        <div id="chat-input-area" class="hidden border-t border-zinc-100 px-4 py-3">
            <div class="flex items-end gap-2">
                <textarea id="chat-message-input" rows="1" aria-label="Message"
                    class="input flex-1 rounded-xl resize-none overflow-hidden"
                    placeholder="Type a message…" style="height:40px; max-height:96px;"></textarea>
                <button type="button" id="btn-send"
                    class="btn btn-primary shrink-0 px-3 rounded-xl"
                    aria-label="Send">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </div>
            <p class="text-[10px] text-zinc-400 mt-1">Enter to send · Shift+Enter for newline</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // On mobile, hide thread panel initially; show contacts
    if (window.innerWidth < 768) {
        $('#thread-panel').addClass('hidden');
        $('#contacts-panel').removeClass('hidden');
    } else {
        $('#thread-panel').removeClass('hidden');
        $('#contacts-panel').removeClass('hidden');
    }
    // Update thread initial when name changes
    var _observer = new MutationObserver(function() {
        var name = $('#thread-name').text();
        var initial = name.split(' ').slice(0,2).map(function(w){return w[0]||'';}).join('').toUpperCase();
        $('#thread-initial').text(initial || '—');
    });
    _observer.observe(document.getElementById('thread-name'), { childList: true, characterData: true, subtree: true });
});
</script>
<script src="{{ asset('js/pages/chat.js') }}"></script>
@endpush
