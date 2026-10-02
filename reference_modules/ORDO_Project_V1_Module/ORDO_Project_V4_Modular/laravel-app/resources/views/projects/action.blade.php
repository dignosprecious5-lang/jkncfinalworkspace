<form class="inline-form" method="post" action="{{ route('projects.action',[$record['id'],$action]) }}">
    @csrf
    @if(!empty($item))<input type="hidden" name="id" value="{{ $item }}">@endif
    @if(!empty($reviewer))<input type="hidden" name="reviewer" value="{{ $reviewer }}">@endif
    <button @disabled($record['closed'] || ($disabled ?? false))>{{ $label }}</button>
</form>
