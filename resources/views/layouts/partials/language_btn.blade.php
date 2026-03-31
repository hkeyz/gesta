@php
    $available_langs = config('constants.langs');
    $active_lang = request()->get('lang', session()->get('user.language', app()->getLocale()));
    if (! array_key_exists($active_lang, $available_langs)) {
        $active_lang = config('app.locale');
    }
@endphp

<details class="tw-dw-dropdown tw-dw-dropdown-end" style="margin: 10px;">
    <summary class="tw-bg-transparent tw-text-white tw-font-medium tw-text-sm md:tw-text-base select-none">
        {{ $available_langs[$active_lang]['full_name'] ?? strtoupper($active_lang) }}
    </summary>
    <ul
        class="tw-p-2 tw-shadow tw-dw-menu tw-dw-dropdown-content tw-z-[1] tw-w-48 md:tw-w-56 tw-bg-white tw-rounded-xl tw-mt-3">
        @foreach ($available_langs as $key => $val)
            <li><a value="{{ $key }}" class="change_lang"> {{ $val['full_name'] }}</a>
            </li>
        @endforeach
    </ul>
</details>
