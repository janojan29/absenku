{{--
    Skeleton Row Component
    
    Usage:
    <x-skeleton-row />
    <x-skeleton-row :cols="5" />
--}}

@props([
    'cols' => 4,
])

<tr class="table-row">
    @for ($i = 0; $i < $cols; $i++)
        <td class="py-3 px-4">
            <div class="skeleton skeleton-text {{ $i === 0 ? 'medium' : ($i === $cols - 1 ? 'short' : 'long') }}"></div>
        </td>
    @endfor
</tr>
