@extends('layouts.app')
@section('title', 'Company Products')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4 pb-8">
    <div class="bg-white border border-gray-100 rounded-md overflow-hidden">
        @include('company.partials.company-header', ['company' => $company])

        <section class="bg-gray-50 p-4 min-h-[760px]">
            <div class="rounded-md border border-gray-200 bg-white overflow-hidden shadow-sm">
                <div class="border-b border-gray-100 px-4 py-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Products Availed</h2>
                            <p class="mt-1 text-sm text-gray-500">This list mirrors live products tied to {{ $company->company_name }} through related deals.</p>
                        </div>
                        <a href="{{ route('products.index') }}" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">+ Open Products</a>
                    </div>

                    @if (session('products_error'))
                        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                            {{ session('products_error') }}
                        </div>
                    @endif

                </div>

                <div class="p-4">
                    <div class="border border-gray-200 rounded-md bg-white overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium">Product Name</th>
                                        <th class="px-4 py-3 text-left font-medium">SKU</th>
                                        <th class="px-4 py-3 text-left font-medium">Category</th>
                                        <th class="px-4 py-3 text-left font-medium">Price</th>
                                        <th class="px-4 py-3 text-left font-medium">Pricing Type</th>
                                        <th class="px-4 py-3 text-left font-medium">Status</th>
                                        <th class="px-4 py-3 text-left font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                                    @forelse ($products as $product)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3">
                                                <a href="{{ $product['show_url'] ?? route('products.index') }}" class="font-medium text-gray-900 hover:text-blue-700">{{ $product['name'] }}</a>
                                                @if (! empty($product['description']))
                                                    <div class="mt-1 text-xs text-gray-500">{{ $product['description'] }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">{{ $product['sku'] }}</td>
                                            <td class="px-4 py-3">{{ $product['category'] }}</td>
                                            <td class="px-4 py-3 font-medium text-gray-900">P{{ number_format((float) $product['price'], 2) }}</td>
                                            <td class="px-4 py-3">{{ $product['pricing_type'] }}</td>
                                            <td class="px-4 py-3">
                                                @php($statusClasses = match($product['status']) {
                                                    'Active' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                                    'Inactive' => 'border-amber-200 bg-amber-50 text-amber-700',
                                                    'Pending Approval' => 'border-blue-200 bg-blue-50 text-blue-700',
                                                    'Rejected' => 'border-red-200 bg-red-50 text-red-700',
                                                    default => 'border-gray-200 bg-gray-100 text-gray-600',
                                                })
                                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium {{ $statusClasses }}">{{ $product['status'] }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <a href="{{ $product['show_url'] ?? route('products.index') }}" class="inline-flex h-8 items-center rounded-full border border-gray-200 px-3 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">No availed products found for this company yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="border-t border-gray-100 px-4 py-3 text-sm text-gray-500">
                        Total revenue: <span class="font-semibold text-gray-900">P{{ number_format($summary['total_value'], 2) }}</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
