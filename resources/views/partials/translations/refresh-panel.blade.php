@php
    use Gingerminds\LaravelCore\Models\User\User;
    use Gingerminds\LaravelMultisite\Models\Site\Site;
    use Gingerminds\LaravelMultisite\Services\Translation\TranslationService;

    $translationService = app(TranslationService::class);
    $translationRefreshSites = Site::all()
        ->filter(fn ($site) => $translationService->isEnabledForSite($site))
        ->values();

    /** @var User|null $user */
    $user = auth()->user();
@endphp

@if(($user?->hasRole('Super-Admin') || $user?->can('manage translations')) && $translationRefreshSites->isNotEmpty())
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('gingerminds-multisite.translations.refresh') }}"
                  class="d-flex align-items-center gap-2">
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
        </div>
    </div>
@endif
