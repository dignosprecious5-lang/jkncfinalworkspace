@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto mt-10 bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6">
    <h2 class="text-base font-bold text-slate-900">Import Products</h2>

    <form action="{{ route('products.import.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <div>
            <label class="block font-semibold text-slate-700 text-xs mb-1">Select CSV or Excel File *</label>
            <input type="file" name="file" required class="w-full border border-slate-300 rounded p-2 text-xs bg-white text-slate-800">
        </div>

        <div class="flex justify-end space-x-2 pt-3 border-t">
            <a href="{{ route('products.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded text-xs font-medium">Cancel</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-xs font-medium">Upload & Import</button>
        </div>
    </form>
</div>
@endsection