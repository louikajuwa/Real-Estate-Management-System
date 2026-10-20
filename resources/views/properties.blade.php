@extends('layouts.app')
@section('title', 'Properties')
@section('page-title', 'Properties')
@section('content')
<div id="properties-page">
    {{-- Filters + Add button --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <select id="filter-status" class="input input-sm" aria-label="Filter by status">
            <option value="">All statuses</option>
            <option value="available">Available</option>
            <option value="reserved">Reserved</option>
            <option value="sold">Sold</option>
        </select>
        <select id="filter-type" class="input input-sm" aria-label="Filter by type">
            <option value="">All types</option>
            <option value="apartment">Apartment</option>
            <option value="house">House</option>
            <option value="villa">Villa</option>
            <option value="land">Land</option>
            <option value="commercial">Commercial</option>
            <option value="office">Office</option>
        </select>
        <input id="filter-city" type="text" placeholder="Filter by city…" aria-label="Filter by city" class="input input-sm w-full sm:w-40">
        <div class="ml-auto flex items-center gap-2">
            {{-- View toggle --}}
            <div class="flex rounded-lg border border-zinc-200 overflow-hidden" role="group" aria-label="View mode">
                <button type="button" id="view-table" class="px-2.5 py-1.5 bg-indigo-50 text-indigo-600" title="Table view" aria-label="Table view" aria-pressed="true">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 6h18M3 14h18M3 18h18"/></svg>
                </button>
                <button type="button" id="view-grid" class="px-2.5 py-1.5 text-zinc-400 hover:text-zinc-600 border-l border-zinc-200" title="Grid view" aria-label="Grid view" aria-pressed="false">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                </button>
            </div>
            @can('create', \App\Models\Property::class)
            <button type="button" id="btn-add-property" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add Property
            </button>
            @endcan
        </div>
    </div>

    {{-- Table view --}}
    <div id="table-view">
        <div id="property-table-container"></div>
    </div>

    {{-- Grid view (hidden by default) --}}
    <div id="grid-view" class="hidden">
        <div id="property-grid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4"></div>
        <div id="grid-pagination" class="flex flex-wrap justify-center gap-1 mt-4" role="navigation" aria-label="Pagination"></div>
    </div>
</div>

{{-- Property Modal --}}
<div id="property-modal" class="modal-backdrop hidden fixed inset-0 z-50 bg-zinc-900/50 items-center justify-center p-4">
    <div role="dialog" aria-modal="true" aria-labelledby="modal-title" class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-100">
            <h2 class="text-base font-semibold text-zinc-900" id="modal-title">Add Property</h2>
            <button type="button" data-modal-close class="btn-icon p-1" aria-label="Close">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="property-form" class="px-6 py-4 space-y-4">
            <input type="hidden" id="property-id">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="f-title" class="label">Title *</label>
                    <input type="text" id="f-title" name="title" required class="input w-full" placeholder="Sea View Apartment">
                    <p class="text-xs text-rose-600 mt-1 hidden field-error" id="err-title"></p>
                </div>
                <div>
                    <label for="f-type" class="label">Type *</label>
                    <select id="f-type" name="type" class="input w-full">
                        <option value="apartment">Apartment</option>
                        <option value="house">House</option>
                        <option value="villa">Villa</option>
                        <option value="land">Land</option>
                        <option value="commercial">Commercial</option>
                        <option value="office">Office</option>
                    </select>
                    <p class="text-xs text-rose-600 mt-1 hidden field-error" id="err-type"></p>
                </div>
                <div>
                    <label for="f-status" class="label">Status</label>
                    <select id="f-status" name="status" class="input w-full">
                        <option value="available">Available</option>
                        <option value="reserved">Reserved</option>
                        <option value="sold">Sold</option>
                    </select>
                </div>
                <div>
                    <label for="f-price" class="label">Price (AED) *</label>
                    <input type="number" id="f-price" name="price" required min="0" step="0.01" class="input w-full" placeholder="250000">
                    <p class="text-xs text-rose-600 mt-1 hidden field-error" id="err-price"></p>
                </div>
                <div>
                    <label for="f-city" class="label">City *</label>
                    <input type="text" id="f-city" name="city" required class="input w-full" placeholder="Dubai">
                    <p class="text-xs text-rose-600 mt-1 hidden field-error" id="err-city"></p>
                </div>
                <div class="sm:col-span-2">
                    <label for="f-address" class="label">Address *</label>
                    <input type="text" id="f-address" name="address" required class="input w-full" placeholder="123 Marina Walk, Dubai">
                    <p class="text-xs text-rose-600 mt-1 hidden field-error" id="err-address"></p>
                </div>
                <div>
                    <label for="f-bedrooms" class="label">Bedrooms</label>
                    <input type="number" id="f-bedrooms" name="bedrooms" min="0" class="input w-full" placeholder="3">
                </div>
                <div>
                    <label for="f-bathrooms" class="label">Bathrooms</label>
                    <input type="number" id="f-bathrooms" name="bathrooms" min="0" class="input w-full" placeholder="2">
                </div>
                <div>
                    <label for="f-area" class="label">Area (sqft)</label>
                    <input type="number" id="f-area" name="area_sqft" min="0" class="input w-full" placeholder="1200">
                </div>
                <div class="flex items-center gap-2 pt-5">
                    <input type="checkbox" id="f-featured" name="is_featured" class="w-4 h-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="f-featured" class="text-sm text-zinc-700">Featured property</label>
                </div>
                <div id="agent-field-wrap" class="sm:col-span-2 hidden">
                    <label for="f-agent" class="label">Agent *</label>
                    <select id="f-agent" name="agent_id" class="input w-full">
                        <option value="">Select agent…</option>
                    </select>
                    <p class="text-xs text-rose-600 mt-1 hidden field-error" id="err-agent_id"></p>
                </div>
                <div class="sm:col-span-2">
                    <label for="f-description" class="label">Description</label>
                    <textarea id="f-description" name="description" rows="3" class="input w-full resize-none" placeholder="Describe the property…"></textarea>
                </div>
                {{-- Image upload --}}
                <div class="sm:col-span-2" id="image-upload-section">
                    <label for="image-input" class="label">Images (max 10, JPG/PNG/WebP, 5MB each)</label>
                    <div id="image-dropzone" role="button" tabindex="0" aria-label="Choose images to upload" class="border-2 border-dashed border-zinc-200 rounded-xl p-6 text-center cursor-pointer hover:border-indigo-300 hover:bg-indigo-50/30 transition-colors">
                        <svg class="w-8 h-8 text-zinc-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <p class="text-xs text-zinc-500">Click or drag to upload images</p>
                        <input type="file" id="image-input" multiple accept="image/jpeg,image/png,image/webp" class="sr-only" tabindex="-1">
                    </div>
                    <div id="image-thumbnails" class="flex flex-wrap gap-2 mt-2"></div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-100">
                <button type="button" data-modal-close class="btn btn-secondary">Cancel</button>
                <button type="submit" id="property-save-btn" class="btn btn-primary">
                    <span id="save-btn-text">Save</span>
                    <svg id="save-spinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Status modal (quick status change) --}}
<div id="status-modal" class="modal-backdrop hidden fixed inset-0 z-50 bg-zinc-900/50 items-center justify-center p-4">
    <div role="dialog" aria-modal="true" aria-labelledby="status-modal-heading" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 max-h-[90vh] overflow-y-auto">
        <h2 id="status-modal-heading" class="text-base font-semibold text-zinc-900 mb-4">Update Status</h2>
        <input type="hidden" id="status-property-id">
        <div class="space-y-2 mb-6" role="radiogroup" aria-labelledby="status-modal-heading">
            <label class="flex items-center gap-3 p-3 rounded-lg border border-zinc-200 cursor-pointer hover:bg-zinc-50">
                <input type="radio" name="new-status" value="available" class="text-indigo-600"> <span class="text-sm text-zinc-700">Available</span>
                <span class="ml-auto text-xs font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">Available</span>
            </label>
            <label class="flex items-center gap-3 p-3 rounded-lg border border-zinc-200 cursor-pointer hover:bg-zinc-50">
                <input type="radio" name="new-status" value="reserved" class="text-indigo-600"> <span class="text-sm text-zinc-700">Reserved</span>
                <span class="ml-auto text-xs font-medium text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full">Reserved</span>
            </label>
            <label class="flex items-center gap-3 p-3 rounded-lg border border-zinc-200 cursor-pointer hover:bg-zinc-50">
                <input type="radio" name="new-status" value="sold" class="text-indigo-600"> <span class="text-sm text-zinc-700">Sold</span>
                <span class="ml-auto text-xs font-medium text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full">Sold</span>
            </label>
        </div>
        <div class="flex gap-2">
            <button type="button" data-modal-close class="btn btn-secondary flex-1">Cancel</button>
            <button type="button" id="status-save-btn" class="flex-1 btn btn-primary">Save</button>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/pages/properties.js') }}"></script>
@endpush