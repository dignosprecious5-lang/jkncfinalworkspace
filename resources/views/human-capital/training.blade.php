@extends('layouts.app')

@section('content')

<div
    x-data="{
        openSlider: false,

        formMode: 'create',

        formTitle: 'Add Training',

        formAction: '{{ route('human-capital.training.store') }}',

        trainingData: {
            title: '',
            description: '',
            provider: '',
            duration_value: '',
            duration_unit: 'days',
        }
    }"
    class="w-full px-6 mt-4 h-[calc(100vh-100px)] flex flex-col"
>

    <div class="bg-white rounded-xl border border-gray-200 flex flex-col flex-grow min-h-0">

        <div class="flex items-center justify-between px-5 py-4 border-b">

            <div>
                <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Human Capital</p>
                <h1 class="text-lg font-semibold text-gray-900">Training Library</h1>
                <p class="text-xs text-gray-500">Programs, assignments, completion, and certificate issuance.</p>
            </div>

            @if($canManageTraining)
            <button
                @click="
                    openSlider = true;

                    formMode = 'create';

                    formTitle = 'Add Training';

                    formAction = '{{ route('human-capital.training.store') }}';

                    trainingData = {
                        title: '',
                        description: '',
                        provider: '',
                        duration_value: '',
                        duration_unit: 'days',
                    };
                "
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-semibold"
            >
                + Add Training
            </button>
            @endif

        </div>

        <!-- SUCCESS MESSAGE -->
        @if(session('success'))
            <div class="mx-4 mt-4 p-3 rounded bg-green-100 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <!-- TABLE -->
        <div class="p-4 flex-grow overflow-hidden">

            <div class="border rounded-xl h-full overflow-auto bg-white">

                <table class="w-full min-w-[1280px] text-sm border-collapse">

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

                            <th class="w-[520px] p-3 text-left">
                                Assigned Employees
                            </th>

                            <th class="w-40 p-3 text-right">
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <!-- TABLE BODY -->
                    <tbody>

                        @forelse($trainings as $training)

                            <tr class="border-t hover:bg-gray-50">

                                <td class="p-3 align-top">
                                    <div class="font-semibold text-gray-900">{{ $training->title }}</div>
                                    <div class="mt-1 text-xs text-gray-500">{{ $training->assignments->count() }} assigned</div>
                                </td>

                                <td class="p-3 align-top text-gray-600">
                                    {{ $training->description }}
                                </td>

                                <td class="p-3 align-top text-gray-600">
                                    {{ $training->provider }}
                                </td>

                                <td class="p-3 align-top text-gray-600">
                                    {{ $training->formatted_duration }}
                                </td>

                                <td class="p-3 align-top">

                                    @forelse($training->assignments->take(4) as $assignment)

                                    <div class="mb-2 grid grid-cols-[minmax(0,1fr)_auto] gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs">

                                        <div class="min-w-0">
                                            <div class="truncate font-semibold text-gray-900">
                                                {{ $assignment->employee?->full_name ?? 'Employee deleted' }}
                                            </div>
                                            <div class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-gray-500">
                                                <span>{{ $assignment->start_date?->format('M d, Y') ?? 'No start date' }}</span>
                                                <span>to</span>
                                                <span>{{ $assignment->due_date?->format('M d, Y') ?? 'No due date' }}</span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">

                                            <!-- STATUS -->
                                            <span class="rounded-full px-2 py-0.5 font-medium
                                                {{ $assignment->status === 'Completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                                {{ $assignment->status }}
                                            </span>

                                            @if($canManageTraining && !$assignment->completed_at)
                                                <form method="POST" action="{{ route('human-capital.training.complete', $assignment->id) }}">
                                                    @csrf
                                                    <button class="rounded-md bg-emerald-600 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-emerald-700">
                                                        Complete
                                                    </button>
                                                </form>
                                            @endif

                                            @if($canManageTraining && $assignment->completed_at && !$assignment->certificate_issued)
                                                <form method="POST" action="{{ route('human-capital.training.certificate', $assignment->id) }}">
                                                    @csrf
                                                    <button class="rounded-md bg-blue-600 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-blue-700">
                                                        Issue Cert
                                                    </button>
                                                </form>
                                            @endif

                                            <!-- CERTIFIED -->
                                            @if($assignment->certificate_issued)
                                                <span class="rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                                                    Certificate Issued
                                                </span>
                                            @endif

                                        </div>

                                    </div>

                                    @empty
                                        <span class="text-xs text-gray-400">No assignments yet</span>
                                    @endforelse

                                    @if($training->assignments->count() > 4)

                                        <div class="mt-1 text-xs text-gray-400">
                                            +{{ $training->assignments->count() - 4 }} more
                                        </div>

                                    @endif

                                </td>

                                <td class="p-3 align-top text-right">

                                    @if($canManageTraining)
                                    <div class="inline-flex overflow-hidden rounded-lg border border-gray-200 bg-white">

                                        <!-- EDIT -->
                                        <button
                                            type="button"
                                            class="px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                            @click="
                                                openSlider = true;

                                                formMode = 'edit';

                                                formTitle = 'Edit Training';

                                                formAction = '{{ route('human-capital.training.update', $training->id) }}';

                                                trainingData = {
                                                    title: @js($training->title),
                                                    description: @js($training->description),
                                                    provider: @js($training->provider),
                                                    duration_value: @js($training->duration_value),
                                                    duration_unit: @js($training->duration_unit),
                                                };
                                            "
                                        >
                                            Edit
                                        </button>

                                        <!-- DELETE -->
                                        <form
                                            action="{{ route('human-capital.training.destroy', $training->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Delete this training?')"
                                        >

                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="border-l border-gray-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </div>
                                    @else
                                        <span class="text-xs text-gray-400">View only</span>
                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="6"
                                    class="p-6 text-center text-gray-500"
                                >
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
    <div
        class="fixed inset-0 z-50 flex justify-end"
        x-show="openSlider"
        x-transition
        style="display: none;"
    >

        <!-- OVERLAY -->
        <div
            class="absolute inset-0 bg-black bg-opacity-40"
            @click="openSlider = false"
        ></div>

        <!-- PANEL -->
        <div class="relative w-[420px] h-full bg-white shadow-xl p-6 flex flex-col">

            <!-- HEADER -->
            <div class="flex items-center justify-between mb-4">

                <h2
                    class="text-lg font-semibold"
                    x-text="formTitle"
                ></h2>

                <button
                    @click="openSlider = false"
                    class="text-gray-500 text-xl"
                >
                    ✕
                </button>

            </div>

            <!-- FORM -->
            <form
                :action="formAction"
                method="POST"
                class="flex flex-col h-full"
            >

                @csrf

                <template x-if="formMode === 'edit'">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="flex-1 overflow-auto">

                    <!-- TITLE -->
                    <div class="mb-3">

                        <input
                            type="text"
                            name="title"
                            placeholder="Training Title"
                            class="w-full border p-2 rounded"
                            x-model="trainingData.title"
                            required
                        >

                    </div>

                    <!-- DESCRIPTION -->
                    <div class="mb-3">

                        <textarea
                            name="description"
                            placeholder="Description"
                            rows="4"
                            class="w-full border p-2 rounded"
                            x-model="trainingData.description"
                        ></textarea>

                    </div>

                    <!-- PROVIDER -->
                    <div class="mb-3">

                        <input
                            type="text"
                            name="provider"
                            placeholder="Provider"
                            class="w-full border p-2 rounded"
                            x-model="trainingData.provider"
                        >

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
                                x-model="trainingData.duration_value"
                                required
                            >

                            <select
                                name="duration_unit"
                                class="border p-2 rounded"
                                x-model="trainingData.duration_unit"
                            >
                                <option value="minutes">Minutes</option>
                                <option value="hours">Hours</option>
                                <option value="days">Days</option>
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
                        class="px-4 py-2 border rounded"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded"
                    >
                        Save
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection
