@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-[#2f6b5c] ']) }}>
        {{ $status }}
    </div>
@endif
