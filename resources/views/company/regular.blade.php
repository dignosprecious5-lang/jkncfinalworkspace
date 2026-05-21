@extends('layouts.app')
@section('title', 'Company Regular')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4 pb-8">
    <div class="bg-white border border-gray-100 rounded-md overflow-hidden">
        @include('company.partials.company-header', ['company' => $company])

        <section class="bg-gray-50 p-4 min-h-[760px]">
            <div class="rounded-md border border-gray-200 bg-white overflow-hidden">
                <div class="border-b border-gray-100 px-4 py-4">
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900">REGULAR</h2>
                    <p class="mt-1 text-sm text-gray-500">Manage regular engagements of the company.</p>

                    <div class="mt-4 grid grid-cols-1 gap-2 lg:grid-cols-12">
                        <div class="relative lg:col-span-4">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input
                                type="text"
                                placeholder="Search engagements..."
                                class="w-full h-10 rounded border border-gray-200 bg-white pl-11 pr-4 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400"
                            >
                        </div>

                        <div class="lg:col-span-2">
                            <select class="w-full h-10 rounded border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                                <option>Frequency: All</option>
                                <option>Frequency: Monthly</option>
                                <option>Frequency: Quarterly</option>
                                <option>Frequency: Annual</option>
                            </select>
                        </div>

                        <div class="lg:col-span-2">
                            <select class="w-full h-10 rounded border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                                <option>Status: All</option>
                                <option>Status: Active</option>
                            </select>
                        </div>

                        <div class="lg:col-span-1">
                            <span class="inline-flex h-10 w-full items-center justify-center rounded border border-gray-200 bg-gray-50 px-2 text-sm font-medium text-gray-600">4</span>
                        </div>

                        <div class="lg:col-span-1">
                            <button class="w-full h-10 rounded border border-gray-200 text-gray-700 text-sm hover:bg-gray-50 inline-flex items-center justify-center">
                                <i class="fas fa-sort text-xs"></i>
                            </button>
                        </div>

                        <div class="lg:col-span-2">
                            <a href="{{ route('regular.index') }}" class="w-full h-10 rounded-full bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700 inline-flex items-center justify-center gap-2">
                                <span class="text-base leading-none">+</span>
                                <span>Open Regular</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="p-4">
                    <div class="border border-gray-200 rounded-md bg-white overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                                    <tr>
                                        <th class="w-10 px-3 py-3 text-left">
                                            <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600">
                                        </th>
                                        <th class="px-3 py-3 text-left font-medium">Engagement Name</th>
                                        <th class="px-3 py-3 text-left font-medium">Frequency</th>
                                        <th class="px-3 py-3 text-left font-medium">Status</th>
                                        <th class="px-3 py-3 text-left font-medium">Start Date</th>
                                        <th class="px-3 py-3 text-left font-medium">Next Billing Date</th>
                                        <th class="px-3 py-3 text-left font-medium">Owner</th>
                                        <th class="px-3 py-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                                    @foreach ($regularEngagements as $engagement)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-3">
                                                <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600">
                                            </td>
                                            <td class="px-3 py-3 font-medium text-gray-800">{{ $engagement['name'] }}</td>
                                            <td class="px-3 py-3">{{ $engagement['frequency'] }}</td>
                                            <td class="px-3 py-3">
                                                <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">{{ $engagement['status'] }}</span>
                                            </td>
                                            <td class="px-3 py-3">{{ $engagement['start_date'] }}</td>
                                            <td class="px-3 py-3">{{ $engagement['next_billing_date'] }}</td>
                                            <td class="px-3 py-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="h-7 w-7 rounded-full bg-gray-100 border border-gray-200 text-[11px] font-semibold text-gray-600 inline-flex items-center justify-center">{{ $engagement['owner_initials'] }}</span>
                                                    <span>{{ $engagement['owner'] }}</span>
                                                </div>
                                            </td>
                                            <td class="px-3 py-3">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ $engagement['show_url'] ?? route('regular.index') }}" class="inline-flex h-8 items-center rounded-full bg-blue-600 px-3 text-xs font-medium text-white hover:bg-blue-700">View</a>
                                                    <button class="h-8 w-8 rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50">
                                                        <i class="fas fa-ellipsis-v text-xs"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 px-4 py-3 flex flex-wrap items-center justify-end gap-3 text-sm text-gray-500">
                    <span>1-4 of 4 results</span>
                    <button class="h-8 rounded border border-gray-200 px-3 hover:bg-gray-50">Previous</button>
                    <button class="h-8 rounded border border-gray-200 px-3 hover:bg-gray-50">Next</button>
                    <select class="h-8 rounded border border-gray-200 bg-white px-2 text-sm text-gray-700">
                        <option>10 per page</option>
                    </select>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
