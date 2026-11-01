@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-[#b7d4ce]  text-sm font-medium leading-5 text-[#172c35]  focus:outline-none focus:border-[#527c70] transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-[#64747a]  hover:text-[#344950]  hover:border-[#cbd3d0]  focus:outline-none focus:text-[#344950]  focus:border-[#cbd3d0]  transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
