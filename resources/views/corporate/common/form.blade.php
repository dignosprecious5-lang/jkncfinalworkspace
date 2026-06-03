@extends('layouts.app')
@section('title', $title)

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4">
    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-100">
            <a href="{{ $cancelRoute }}" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="text-lg font-semibold">{{ $title }}</div>
        </div>

        <div class="p-6">
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <div class="font-semibold mb-1">Please fix the following:</div>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf
                @if ($method !== 'POST')
                    @method($method)
                @endif

                @foreach ($fields as $field)
                    @php
                        $fieldName = $field['name'];
                        $fieldType = $field['type'] ?? 'text';
                        $fieldLabel = $field['label'] ?? ucfirst(str_replace('_', ' ', $fieldName));
                        $fieldRequired = $field['required'] ?? false;
                        $fieldStep = $field['step'] ?? null;
                        $fieldReadonly = $field['readonly'] ?? false;
                        $fieldDisabled = $field['disabled'] ?? false;
                        $value = old($fieldName, data_get($item, $fieldName));
                        $isFile = $fieldType === 'file';
                        $isTextarea = $fieldType === 'textarea';
                        $isSelect = $fieldType === 'select';
                        $fieldOptions = $field['options'] ?? [];
                        $isFullWidth = $isTextarea || $isFile;
                    @endphp

                    <div class="{{ $isFullWidth ? 'md:col-span-2' : '' }}">
                        <label class="text-xs text-gray-600">
                            {{ $fieldLabel }}
                            @if ($fieldRequired)
                                <span class="text-red-500">*</span>
                            @endif
                        </label>

                        @if ($isTextarea)
                            <textarea name="{{ $fieldName }}" rows="3" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" @if ($fieldReadonly) readonly @endif @if ($fieldDisabled) disabled @endif>{{ $value }}</textarea>
                        @elseif ($isSelect)
                            <select name="{{ $fieldName }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm" @if ($fieldRequired) required @endif @if ($fieldReadonly || $fieldDisabled) disabled @endif>
                                @foreach ($fieldOptions as $optionValue => $optionLabel)
                                    @php
                                        if (is_int($optionValue)) {
                                            $optionValue = $optionLabel;
                                        }
                                    @endphp
                                    <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                            @if ($fieldReadonly || $fieldDisabled)
                                <input type="hidden" name="{{ $fieldName }}" value="{{ $value }}">
                            @endif
                        @elseif ($isFile)
                            <input type="file" name="{{ $fieldName }}" class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                            @if ($value)
                                <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                                    <p class="text-xs text-gray-700">Existing file stored.</p>
                                    <label class="mt-2 inline-flex items-center gap-2 text-xs font-medium text-red-700">
                                        <input type="checkbox" name="remove_{{ $fieldName }}" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                        Remove current file
                                    </label>
                                </div>
                            @endif
                        @else
                                <input
                                type="{{ $fieldType }}"
                                name="{{ $fieldName }}"
                                value="{{ $value }}"
                                @if ($fieldStep) step="{{ $fieldStep }}" @endif
                                @if ($fieldRequired) required @endif
                                @if ($fieldReadonly) readonly @endif
                                @if ($fieldDisabled) disabled @endif
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm @if ($fieldReadonly || $fieldDisabled) bg-gray-50 text-gray-600 @endif"
                            >
                        @endif
                    </div>
                @endforeach

                <div class="md:col-span-2 flex items-center gap-2 pt-2">
                    <a href="{{ $cancelRoute }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-900 text-sm font-medium rounded-lg">
                        Cancel
                    </a>
                    <div class="flex-1"></div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
