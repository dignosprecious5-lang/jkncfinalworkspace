@extends('layouts.app')
@section('title', 'Company Activities')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4 pb-8">
    <div class="bg-white border border-gray-100 rounded-md overflow-hidden">
        @include('company.partials.company-header', ['company' => $company])
        @include('partials.activities-app', [
            'appId' => 'companyActivitiesApp',
            'listId' => 'companyActivitiesList',
            'modalId' => 'companyActivityModal',
            'formId' => 'companyActivityForm',
            'title' => 'Activities',
            'description' => 'Manage company activities with the same activity workspace used across the company module.',
            'openUrl' => $activitiesIndexUrl ?? route('activities'),
            'openLabel' => 'Open Main Activities',
            'editable' => true,
            'activities' => $activities,
            'defaultAssignedUser' => $company->owner_name ?: 'John Admin',
            'storeUrl' => route('company.activities.store', $company->id),
            'updateTemplate' => route('company.activities.update', [$company->id, '__ACTIVITY__']),
            'completeTemplate' => route('company.activities.complete', [$company->id, '__ACTIVITY__']),
            'deleteTemplate' => route('company.activities.destroy', [$company->id, '__ACTIVITY__']),
        ])
    </div>
</div>
@endsection
