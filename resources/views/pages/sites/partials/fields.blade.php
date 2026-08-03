<div class="col-lg-8">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <x-gingerminds-core::form.inputs.basic
                        type="text"
                        id="code"
                        :label="__('gingerminds-core::translation.form.code')"
                        :required="true"
                        :value="old('code', isset($site) ? $site->code : null)"
                />
                <x-gingerminds-core::form.inputs.basic
                        type="url"
                        id="url"
                        :label="__('gingerminds-multisite::translation.form.url')"
                        :required="true"
                        :value="old('url', isset($site) ? $site->url : null)"
                />
            </div>
            <div class="row">
                @php
                    $oldFrontUrls = old('front_urls');
                    $frontUrlsValue = is_array($oldFrontUrls)
                        ? implode("\n", $oldFrontUrls)
                        : (isset($site) ? $site->frontUrls->pluck('url')->implode("\n") : null);
                @endphp
                <x-gingerminds-core::form.inputs.textarea
                        id="front_urls"
                        :label="__('gingerminds-multisite::translation.form.front_urls')"
                        size="xl"
                        :required="false"
                        :rows="4"
                        :helper="__('gingerminds-multisite::translation.form.front_urls_helper')"
                        :value="$frontUrlsValue"
                />
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">@lang('gingerminds-multisite::translation.menu.translations')</h5>
            <div class="row mb-3">
                <x-gingerminds-core::form.inputs.basic
                        type="text"
                        id="google_drive_file_id"
                        :label="__('gingerminds-multisite::translation.form.google_drive_file_id')"
                        size="lg"
                        :required="false"
                        :value="old('google_drive_file_id', isset($site) ? $site->google_drive_file_id : null)"
                />
            </div>
            <div class="row">
                @php
                    $googleCredentialsHelper = __('gingerminds-multisite::translation.form.google_service_account_credentials_helper')
                        . ' '
                        . (isset($site) && !empty($site->google_service_account_credentials)
                            ? '(' . __('gingerminds-multisite::translation.form.google_credentials_configured') . ')'
                            : '(' . __('gingerminds-multisite::translation.form.google_credentials_missing') . ')');
                @endphp
                <x-gingerminds-core::form.inputs.textarea
                        id="google_service_account_credentials"
                        :label="__('gingerminds-multisite::translation.form.google_service_account_credentials')"
                        size="xl"
                        :required="false"
                        :rows="6"
                        placeholder='{"type": "service_account", "client_email": "...", "private_key": "...", ...}'
                        :helper="$googleCredentialsHelper"
                />
            </div>
        </div>
    </div>
</div>
<div class="col-lg-4">
    <div class="card">
        <div class="card-body">
            @php
                $oldLanguages = old('languages');
                $siteLanguages = isset($site) ? $site->languages : collect();
                $selectedLanguageIds = collect();
                $defaultLanguageId = null;

                if (is_array($oldLanguages)) {
                    $selectedLanguageIds = collect(array_keys($oldLanguages))
                        ->filter(fn ($id) => !empty($oldLanguages[$id]['id']))
                        ->map(fn ($id) => (int) $id);

                    $defaultLanguage = collect($oldLanguages)
                        ->first(fn ($language) => (int) ($language['is_default'] ?? 0) === 1);
                    $defaultLanguageId = $defaultLanguage['id'] ?? null;
                } else {
                    $selectedLanguageIds = $siteLanguages->pluck('id');
                    $defaultLanguageId = $siteLanguages->firstWhere('pivot.is_default', true)?->id;
                }
            @endphp

            <div class="row mb-3">
                <x-gingerminds-core::form.inputs.select
                        id="languages[]"
                        :label="__('gingerminds-multisite::translation.languages.name_p')"
                        :required="false"
                        size="xl"
                        :multiple="true"
                >
                    @foreach($languages as $language)
                        <option
                                value="{{ $language->id }}"
                                {{ $selectedLanguageIds->contains($language->id) ? 'selected' : '' }}
                        >
                            {{ $language->label }} ({{ strtoupper($language->iso) }})
                        </option>
                    @endforeach
                </x-gingerminds-core::form.inputs.select>
            </div>
            <div class="row">
                <x-gingerminds-core::form.inputs.select
                        id="default_language"
                        :label="__('gingerminds-multisite::translation.form.default_language')"
                        :required="false"
                        size="xl"
                >
                    @foreach($languages as $language)
                        <option
                                value="{{ $language->id }}"
                                {{ (int) $defaultLanguageId === (int) $language->id ? 'selected' : '' }}
                        >
                            {{ $language->label }} ({{ strtoupper($language->iso) }})
                        </option>
                    @endforeach
                </x-gingerminds-core::form.inputs.select>
            </div>
        </div>
    </div>
</div>
