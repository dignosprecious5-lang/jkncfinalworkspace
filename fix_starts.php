<?php

$workspaces = App\Models\Project::whereHas('deal', function($q) {
    $q->where('stage', 'Closed Won');
})->get();

foreach ($workspaces as $workspace) {
    $start = $workspace->starts()->latest()->first();
    if ($start && strtolower((string) $start->status) === 'pending') {
        $start->forceFill(['status' => 'pending_approval'])->save();
        echo "Updated start for project " . $workspace->id . "\n";
    }
}
