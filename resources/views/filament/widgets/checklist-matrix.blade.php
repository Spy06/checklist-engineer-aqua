<x-filament-widgets::widget>
    <x-filament::section heading="Matriks Checklist Bulanan (Tahun {{ $currentYear }})">
        <div class="overflow-x-auto">
            <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/5" style="table-layout: fixed; min-width: 800px;">
                <thead>
                    <tr>
                        <th class="py-3 font-semibold text-sm text-gray-950 dark:text-white bg-gray-50 dark:bg-white/5" style="width: 250px; padding-left: 1rem; padding-right: 1rem;">Form Checklist</th>
                        @foreach($months as $monthName)
                            <th class="py-3 font-semibold text-sm text-center text-gray-950 dark:text-white bg-gray-50 dark:bg-white/5" style="padding-left: 0.5rem; padding-right: 0.5rem; text-align: center;">
                                {{ substr($monthName, 0, 3) }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @foreach($matrix as $row)
                        <tr>
                            <td class="py-3 text-sm text-gray-700 dark:text-gray-300 font-medium" style="padding-left: 1rem; padding-right: 1rem; white-space: normal;">
                                {{ $row['title'] }}
                            </td>
                            @foreach($row['status'] as $isCompleted)
                                <td class="py-3 text-center" style="padding-left: 0.5rem; padding-right: 0.5rem; text-align: center;">
                                    @if($isCompleted)
                                        <div class="inline-flex items-center justify-center text-success-600 bg-success-50 dark:bg-success-900/20 rounded-full p-1" title="Sudah diisi">
                                            <x-heroicon-o-check-circle style="width: 1.5rem; height: 1.5rem; margin: 0 auto;" />
                                        </div>
                                    @else
                                        <div class="inline-flex items-center justify-center text-danger-600 bg-danger-50 dark:bg-danger-900/20 rounded-full p-1" title="Belum diisi">
                                            <x-heroicon-o-x-circle style="width: 1.5rem; height: 1.5rem; margin: 0 auto;" />
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
