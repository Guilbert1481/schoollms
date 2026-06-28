{{--
    Reusable term-number dropdown (Term 1 / Term 2 / Term 3 by default).

    Usage:
        <x-forms.term-select name="term_number" :selected="old('term_number')" required />
        <x-forms.term-select :options="['1'=>'First','2'=>'Second']" placeholder="Pick a term" />
--}}
@props([
    'name' => 'term_number',
    'id' => null,
    'selected' => null,
    'required' => false,
    'placeholder' => 'Select term',
    'options' => ['1' => 'Term 1', '2' => 'Term 2', '3' => 'Term 3'],
])

<select
    name="{{ $name }}"
    id="{{ $id ?? $name }}"
    @required($required)
    {{ $attributes->merge(['class' => 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100']) }}>
    @if($placeholder !== false)
        <option value="">{{ $placeholder }}</option>
    @endif
    @foreach($options as $value => $label)
        <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
    @endforeach
</select>
