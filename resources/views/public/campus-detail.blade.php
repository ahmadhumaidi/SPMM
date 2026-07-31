<x-layouts.public title="{{ $campus->name }}" :campus="$campus">
    @include('public.partials.simple-campus-page', ['campus' => $campus])
</x-layouts.public>
