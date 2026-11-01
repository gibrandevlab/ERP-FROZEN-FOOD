@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-[#344950] ']) }}>
    {{ $value ?? $slot }}
</label>
