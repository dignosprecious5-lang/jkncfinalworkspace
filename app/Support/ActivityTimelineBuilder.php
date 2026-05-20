<?php

namespace App\Support;

use App\Models\Call;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ActivityTimelineBuilder
{
    public function forContact(Contact $contact): array
    {
        return $this->linkedActivities($this->contactTags($contact));
    }

    public function forCompany(Company|array $company): array
    {
        return $this->linkedActivities($this->companyTags($company));
    }

    private function linkedActivities(array $tags): array
    {
        if ($tags === []) {
            return [];
        }

        return collect()
            ->merge($this->tasks($tags))
            ->merge($this->events($tags))
            ->merge($this->calls($tags))
            ->merge($this->meetings($tags))
            ->sortByDesc('sortAt')
            ->map(fn (array $activity): array => collect($activity)->except('sortAt')->all())
            ->values()
            ->all();
    }

    private function tasks(array $tags): Collection
    {
        if (! Schema::hasTable('tasks') || ! Schema::hasColumn('tasks', 'related_to')) {
            return collect();
        }

        return Task::query()
            ->latest()
            ->get()
            ->filter(fn (Task $task): bool => $this->matchesTags($task->related_to, $tags))
            ->map(fn (Task $task): array => [
                'id' => 'main-task-'.$task->id,
                'source' => 'main',
                'readonly' => true,
                'type' => 'Task',
                'icon' => 'fa-square-check',
                'description' => $task->description ?: $task->name,
                'when' => $this->formatWhen($task->due_date),
                'owner' => $task->owner ?: '-',
                'status' => $task->status ?: 'Open',
                'notes' => $task->name,
                'dueAt' => null,
                'sortAt' => $this->sortAt($task->due_date, $task->created_at),
            ]);
    }

    private function events(array $tags): Collection
    {
        if (! Schema::hasTable('events') || ! Schema::hasColumn('events', 'related_to')) {
            return collect();
        }

        return Event::query()
            ->latest()
            ->get()
            ->filter(fn (Event $event): bool => $this->matchesTags($event->related_to, $tags))
            ->map(fn (Event $event): array => [
                'id' => 'main-event-'.$event->id,
                'source' => 'main',
                'readonly' => true,
                'type' => 'Event',
                'icon' => 'fa-calendar-days',
                'description' => $event->title,
                'when' => $this->formatWhen($event->from),
                'owner' => $event->host ?: '-',
                'status' => 'Scheduled',
                'notes' => $event->to ? 'Until '.$event->to : null,
                'dueAt' => null,
                'sortAt' => $this->sortAt($event->from, $event->created_at),
            ]);
    }

    private function calls(array $tags): Collection
    {
        if (! Schema::hasTable('calls') || ! Schema::hasColumn('calls', 'related_to')) {
            return collect();
        }

        return Call::query()
            ->latest()
            ->get()
            ->filter(fn (Call $call): bool => $this->matchesTags($call->related_to, $tags))
            ->map(fn (Call $call): array => [
                'id' => 'main-call-'.$call->id,
                'source' => 'main',
                'readonly' => true,
                'type' => 'Call',
                'icon' => 'fa-phone',
                'description' => $call->purpose ?: $call->agenda ?: $call->contact ?: $call->to,
                'when' => $this->formatWhen(trim(collect([$call->start_time, $call->start_hour])->filter()->implode(' '))),
                'owner' => $call->owner ?: $call->from ?: '-',
                'status' => $call->completed ? 'Completed' : 'Pending',
                'notes' => $call->agenda,
                'dueAt' => null,
                'sortAt' => $this->sortAt(trim(collect([$call->start_time, $call->start_hour])->filter()->implode(' ')), $call->created_at),
            ]);
    }

    private function meetings(array $tags): Collection
    {
        if (! Schema::hasTable('meetings') || ! Schema::hasColumn('meetings', 'related_to')) {
            return collect();
        }

        return Meeting::query()
            ->latest()
            ->get()
            ->filter(fn (Meeting $meeting): bool => $this->matchesTags($meeting->related_to, $tags))
            ->map(function (Meeting $meeting): array {
                $startsAt = trim(collect([$meeting->date, $meeting->time])->filter()->implode(' '));

                return [
                    'id' => 'main-meeting-'.$meeting->id,
                    'source' => 'main',
                    'readonly' => true,
                    'type' => 'Meeting',
                    'icon' => 'fa-video',
                    'description' => $meeting->description ?: $meeting->title,
                    'when' => $this->formatWhen($startsAt),
                    'owner' => $meeting->owner ?: '-',
                    'status' => Str::headline($meeting->status ?: 'Upcoming'),
                    'notes' => $meeting->title,
                    'dueAt' => null,
                    'sortAt' => $this->sortAt($startsAt, $meeting->created_at),
                ];
            });
    }

    private function matchesTags(?string $relatedTo, array $tags): bool
    {
        if (blank($relatedTo) || $tags === []) {
            return false;
        }

        $activityTags = collect(explode(',', (string) $relatedTo))
            ->map(fn (string $tag): string => $this->normalizeTag($tag))
            ->filter()
            ->values()
            ->all();

        foreach ($activityTags as $activityTag) {
            foreach ($tags as $tag) {
                if ($activityTag === $tag) {
                    return true;
                }

                if (Str::contains($activityTag, $tag)) {
                    return true;
                }

                if (Str::contains($tag, $activityTag)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function contactTags(Contact $contact): array
    {
        $fullName = trim(collect([
            $contact->first_name,
            $contact->middle_name ?: $contact->middle_initial,
            $contact->last_name,
            $contact->name_extension,
        ])->filter()->implode(' '));

        $simpleName = trim(($contact->first_name ?? '').' '.($contact->last_name ?? ''));

        $labelWithCompany = trim(collect([
            $simpleName,
            $contact->company_name,
        ])->filter()->implode(' - '));

        return $this->normalizeTags([
            $fullName,
            $simpleName,
            $labelWithCompany,
            $contact->company_name,
            $contact->email,
            $contact->phone,
            $contact->cif_no ?? null,
        ]);
    }

    private function companyTags(Company|array $company): array
    {
        $value = fn (string $key) => $company instanceof Company ? $company->{$key} ?? null : $company[$key] ?? null;

        return $this->normalizeTags([
            $value('company_name'),
            $value('email'),
            $value('phone'),
        ]);
    }

    private function normalizeTags(array $tags): array
    {
        return collect($tags)
            ->map(fn (mixed $tag): string => $this->normalizeTag((string) $tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeTag(string $tag): string
    {
        return Str::lower(Str::squish($tag));
    }

    private function formatWhen(?string $value): string
    {
        if (blank($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('M d, Y h:i A');
        } catch (\Throwable) {
            return $value;
        }
    }

    private function sortAt(?string $value, mixed $fallback): int
    {
        try {
            return filled($value) ? Carbon::parse($value)->timestamp : Carbon::parse($fallback)->timestamp;
        } catch (\Throwable) {
            return 0;
        }
    }
}