@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand :name="config('app.name', 'Sistem Checklist Aqua')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-cyan-600 dark:bg-cyan-500 text-white">
            <x-app-logo-icon class="size-5" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('app.name', 'Sistem Checklist Aqua')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-cyan-600 dark:bg-cyan-500 text-white">
            <x-app-logo-icon class="size-5" />
        </x-slot>
    </flux:brand>
@endif
