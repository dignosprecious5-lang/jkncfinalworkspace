@include('services.show', [
    'backRoute' => route('company.services.index', $company->id),
    'backLabel' => 'Back to Company Services',
])
