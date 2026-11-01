@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-md border border-[#cbd3d0] bg-white px-3.5 py-2.5 text-sm text-[#172c35] placeholder:text-[#9aa6a3] shadow-none transition-colors focus:border-[#527c70] focus:outline-none focus:ring-2 focus:ring-[#527c70]/20 disabled:bg-[#f5f6f3] disabled:text-[#71817f]']) }}>
