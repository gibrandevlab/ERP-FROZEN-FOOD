@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-[#b7d4ce]  text-start text-base font-medium text-[#17343d]  bg-[#f5f6f3]  focus:outline-none focus:text-[#17343d]  focus:bg-[#e8efeb]  focus:border-[#527c70]  transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-[#52646a]  hover:text-[#344950]  hover:bg-[#f5f6f3]  hover:border-[#cbd3d0]  focus:outline-none focus:text-[#344950]  focus:bg-[#f5f6f3]  focus:border-[#cbd3d0]  transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
