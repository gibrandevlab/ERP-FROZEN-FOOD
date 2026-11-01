<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-[#244e49] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-wider shadow-none hover:bg-[#173d38] active:bg-[#17343d] focus:outline-none focus:ring-2 focus:ring-[#527c70] focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed']) }}>
    {{ $slot }}
</button>
