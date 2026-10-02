<div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Project Attachments &amp; Evidence Repository</h2>
            <p class="text-xs text-slate-500">Supporting documents, technical specifications, and deliverables for {{ $project->project_code }}</p>
        </div>
    </div>

    <div class="space-y-3">
        @forelse(collect($start->attachments ?? []) as $attachment)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 transition hover:bg-slate-50">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-100 text-blue-800 font-bold text-xs">
                        <i class="fas fa-file-alt"></i>
                    </span>
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">{{ $attachment['name'] ?? 'Evidence Document' }}</p>
                        <p class="text-xs text-slate-400">
                            {{ strtoupper(pathinfo((string) ($attachment['name'] ?? ''), PATHINFO_EXTENSION) ?: 'FILE') }}
                            @if (filled($attachment['size'] ?? null))
                                &bull; {{ number_format(((int) $attachment['size']) / 1024, 1) }} KB
                            @endif
                        </p>
                    </div>
                </div>
                @if (filled($attachment['path'] ?? null))
                    <a href="{{ route('uploads.show', ['path' => $attachment['path'], 'download' => 1]) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-xs">
                        <i class="fas fa-download"></i> Download
                    </a>
                @endif
            </div>
        @empty
            <div class="py-12 text-center text-slate-400 text-sm">
                <i class="fas fa-folder-open text-3xl mb-2 block text-slate-300"></i>
                No attachments uploaded yet. You can attach project scope files directly in the START/SOW form.
            </div>
        @endforelse
    </div>
</div>
