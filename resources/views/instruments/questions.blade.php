<div id="questions">
@for($index = 0; $index < 5; $index++)
<div class="field"><label for="questions-{{ $index }}">Butir {{ $index + 1 }}</label><textarea id="questions-{{ $index }}" name="questions[{{ $index }}]" required maxlength="2000">{{ old('questions.'.$index, $questions[$index] ?? '') }}</textarea></div>
@endfor
</div>
