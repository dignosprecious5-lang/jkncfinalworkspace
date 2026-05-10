@extends('layouts.app')

@section('content')

<div x-data="{ openSlider: false }"
    class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col">

    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">

        <!-- HEADER -->
        <div class="flex items-center justify-between px-4 py-3 border-b">

            <h1 class="text-lg font-semibold text-gray-900">
                Training Library
            </h1>

            <button
                @click="openSlider = true"
                class="bg-blue-600 text-white px-5 py-2 rounded text-sm">

                + Add Training
            </button>
        </div>

        <!-- SUCCESS MESSAGE -->
        @if(session('success'))
            <div class="mx-4 mt-4 p-3 rounded bg-green-100 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <!-- TABLE -->
        <div class="p-4 flex-grow overflow-hidden">

            <div class="border rounded-md h-full overflow-auto bg-white">

                <table class="w-full text-sm table-fixed border-collapse">

                    <!-- TABLE HEADER -->
                    <thead class="bg-gray-50 text-gray-600 sticky top-0">

                        <tr>
                            <th class="w-64 p-3 text-left">
                                Training Title
                            </th>

                            <th class="p-3 text-left">
                                Description
                            </th>

                            <th class="w-48 p-3 text-left">
                                Provider
                            </th>

                            <th class="w-40 p-3 text-left">
                                Duration
                            </th>

                            <th class="w-32 p-3 text-left">
                                Action
                            </th>
                        </tr>

                    </thead>

                    <!-- TABLE BODY -->
                    <tbody>

                        @forelse($trainings as $training)

                            <tr class="border-t hover:bg-gray-50">

                                <td class="p-3 font-medium">
                                    {{ $training->title }}
                                </td>

                                <td class="p-3">
                                    {{ $training->description }}
                                </td>

                                <td class="p-3">
                                    {{ $training->provider }}
                                </td>

                                <td class="p-3">
                                    {{ $training->formatted_duration }}
                                </td>

                                <td class="p-3">

                                    <button class="text-blue-600 text-sm">
                                        Edit
                                    </button>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="5"
                                    class="p-6 text-center text-gray-500">

                                    No trainings found.

                                </td>

                            </tr>

                        @endforelse

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

                <h2 class="text-lg font-semibold">
                    Add Training
                </h2>

                <button
                    @click="openSlider = false"
                    class="text-gray-500 text-xl">

                    ✕

                </button>

            </div>

            <!-- FORM -->
            <form action="{{ route('human-capital.training.store') }}"
                method="POST"
                class="flex flex-col h-full">

                @csrf

                <div class="flex-1 overflow-auto">

                    <!-- TITLE -->
                    <div class="mb-3">

                        <input
                            type="text"
                            name="title"
                            placeholder="Training Title"
                            class="w-full border p-2 rounded"
                            required>

                        @error('title')
                            <p class="text-red-500 text-xs mt-1">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    <!-- DESCRIPTION -->
                    <div class="mb-3">

                        <textarea
                            name="description"
                            placeholder="Description"
                            rows="4"
                            class="w-full border p-2 rounded"></textarea>

                    </div>

                    <!-- PROVIDER -->
                    <div class="mb-3">

                        <input
                            type="text"
                            name="provider"
                            placeholder="Provider"
                            class="w-full border p-2 rounded">

                    </div>

                    <!-- DURATION -->
                    <div class="mb-3">

                        <div class="flex gap-2">
                            <input
                                type="number"
                                name="duration_value"
                                placeholder="Duration"
                                min="1"
                                class="flex-1 border p-2 rounded"
                                required>

                            <select
                                name="duration_unit"
                                class="border p-2 rounded">
                                <option value="minutes">Minutes</option>
                                <option value="hours">Hours</option>
                                <option value="days" selected>Days</option>
                                <option value="weeks">Weeks</option>
                                <option value="months">Months</option>
                            </select>
                        </div>

                    </div>

                </div>

                <!-- FOOTER -->
                <div class="pt-4 border-t flex justify-end gap-2">

                    <button
                        type="button"
                        @click="openSlider = false"
                        class="px-4 py-2 border rounded">

                        Cancel

                    </button>

                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded">

                        Save

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection