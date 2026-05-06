@extends('layouts.app')

@section('content')
<div x-data="{ openSlider: false }" class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col">

    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">

        <!-- HEADER -->
        <div class="flex items-center justify-between px-4 py-3 border-b">
            <h1 class="text-lg font-semibold text-gray-900">Training Library</h1>

            <button @click="openSlider = true"
                class="bg-blue-600 text-white px-5 py-2 rounded text-sm">
                + Add Training
            </button>
        </div>

        <!-- TABLE -->
        <div class="p-4 flex-grow overflow-hidden">
            <div class="border rounded-md h-full overflow-auto bg-white">
                <table class="w-full text-sm table-fixed border-collapse">

                    <thead class="bg-gray-50 text-gray-600 sticky top-0">
                        <tr>
                            <th class="w-64 p-3 text-left">Training Title</th>
                            <th class="p-3 text-left">Description</th>
                            <th class="w-48 p-3 text-left">Provider</th>
                            <th class="w-40 p-3 text-left">Duration</th>
                            <th class="w-32 p-3 text-left">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <!-- STATIC DATA -->
                        @php
                        $trainings = [
                            [
                                'title' => 'Fire Safety Training',
                                'description' => 'Basic fire prevention and response',
                                'provider' => 'BFP Cebu',
                                'duration' => '2 days'
                            ],
                            [
                                'title' => 'Leadership Seminar',
                                'description' => 'Leadership and team management',
                                'provider' => 'HR Academy PH',
                                'duration' => '3 days'
                            ],
                            [
                                'title' => 'Customer Service Training',
                                'description' => 'Customer handling and communication',
                                'provider' => 'TESDA',
                                'duration' => '1 day'
                            ],
                            [
                                'title' => 'Cybersecurity Awareness',
                                'description' => 'Basic cybersecurity practices',
                                'provider' => 'DICT',
                                'duration' => '4 hours'
                            ],
                            [
                                'title' => 'First Aid Training',
                                'description' => 'Basic life support and emergency care',
                                'provider' => 'Red Cross Cebu',
                                'duration' => '2 days'
                            ]
                        ];
                        @endphp

                        @foreach($trainings as $training)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 font-medium">{{ $training['title'] }}</td>
                            <td class="p-3">{{ $training['description'] }}</td>
                            <td class="p-3">{{ $training['provider'] }}</td>
                            <td class="p-3">{{ $training['duration'] }}</td>

                            <td class="p-3">
                                <button class="text-blue-600 text-sm">Edit</button>
                            </td>
                        </tr>
                        @endforeach

                    </tbody>

                </table>
            </div>
        </div>
    </div>

    <!-- SLIDER -->
    <div class="fixed inset-0 z-50 flex justify-end"
        x-show="openSlider"
        x-transition
        style="display: none;">

        <!-- OVERLAY -->
        <div class="absolute inset-0 bg-black bg-opacity-40"
             @click="openSlider = false"></div>

        <!-- PANEL -->
        <div class="relative w-[420px] h-full bg-white shadow-xl p-6 flex flex-col">

            <!-- HEADER -->
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Add Training</h2>
                <button @click="openSlider = false" class="text-gray-500">✕</button>
            </div>

            <!-- FORM -->
            <div class="flex-1 overflow-auto">

                <input type="text" placeholder="Training Title"
                    class="w-full border p-2 mb-3 rounded">

                <textarea placeholder="Description"
                    class="w-full border p-2 mb-3 rounded"></textarea>

                <input type="text" placeholder="Provider"
                    class="w-full border p-2 mb-3 rounded">

                <input type="text" placeholder="Duration (e.g. 2 days)"
                    class="w-full border p-2 mb-3 rounded">

            </div>

            <!-- FOOTER -->
            <div class="pt-4 border-t flex justify-end gap-2">
                <button @click="openSlider = false"
                    class="px-4 py-2 border rounded">
                    Cancel
                </button>

                <button type="button"
                    class="px-4 py-2 bg-blue-600 text-white rounded opacity-50 cursor-not-allowed">
                    Save (Disabled)
                </button>
            </div>

        </div>
    </div>

</div>
@endsection