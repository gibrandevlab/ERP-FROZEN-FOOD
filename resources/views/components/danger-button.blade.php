<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-[#9c5252] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-wider shadow-none hover:bg-[#7a3d3d] active:bg-[#6b3434] focus:outline-none focus:ring-2 focus:ring-[#9c5252] focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
