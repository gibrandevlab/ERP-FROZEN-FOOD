<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-[#cbd3d0] rounded-md font-semibold text-xs text-[#344950] uppercase tracking-wider shadow-none hover:bg-[#f5f6f3] hover:border-[#a9beb5] focus:outline-none focus:ring-2 focus:ring-[#527c70] focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
