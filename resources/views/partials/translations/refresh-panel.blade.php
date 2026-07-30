@php
    $translationService = app(\Gingerminds\LaravelMultisite\Services\Translation\TranslationService::class);
    $translationRefreshSites = \Gingerminds\LaravelMultisite\Models\Site\Site::all()
        ->filter(fn ($site) => $translationService->isEnabledForSite($site))
        ->values();
@endphp

@if(auth()->user()?->can('manage translations') && $translationRefreshSites->isNotEmpty())
    <form method="POST" action="{{ route('gingerminds-multisite.translations.refresh') }}" class="d-flex align-items-center gap-2">
        @csrf
        <select name="site_id" class="form-select form-select-sm" required>
            @foreach($translationRefreshSites as $site)
                <option value="{{ $site->id }}">{{ $site->code }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">
            <i class="bi bi-arrow-repeat me-1"></i>@lang('gingerminds-multisite::translation.translations.refresh_action')
        </button>
    </form>
@endif
