@props([
    'languages',
    'translations' => [],
    'fieldsView' => null,
    'defaultLanguage' => null,
    'extra' => [],
])

@php
    $translations = collect($translations)->keyBy('language_id');
@endphp

<ul class="nav nav-tabs">
    @foreach($languages as $language)
        <li class="nav-item">
            <button type="button" class="nav-link @if($loop->first) active @endif"
                    data-bs-toggle="tab"
                    data-bs-target="#lang-{{ $language->id }}">
                {{ strtoupper($language->iso) }}
            </button>
        </li>
    @endforeach
</ul>

<div class="tab-content pt-3">
    @foreach($languages as $language)
        @php
            $translation = $translations[$language->id] ?? null;
        @endphp

        <div class="tab-pane fade @if($loop->first) show active @endif"
             id="lang-{{ $language->id }}">

            @if($fieldsView)
                @include($fieldsView, array_merge($extra, [
                    'language' => $language,
                    'translation' => $translation,
                    'required' => $language->id === $defaultLanguage?->id
                ]))
            @endif

        </div>
    @endforeach
</div>
