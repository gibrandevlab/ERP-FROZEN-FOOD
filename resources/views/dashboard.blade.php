<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[#344950]  leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white  overflow-hidden shadow-none sm:rounded-lg">
                <div class="p-6 text-[#172c35] ">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
