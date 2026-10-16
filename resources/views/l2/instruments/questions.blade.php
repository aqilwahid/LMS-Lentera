<div id="questions">
@foreach($questions as $index => $question)
<fieldset class="field" id="questions-{{ $index }}"><legend>Soal {{ $index + 1 }}</legend>
<div class="field"><label for="questions-{{ $index }}-text">Teks soal {{ $index + 1 }}</label><textarea id="questions-{{ $index }}-text" name="questions[{{ $index }}][text]" maxlength="2000" required>{{ old('questions.'.$index.'.text',$question['text']) }}</textarea></div>
@foreach(range(0,3) as $option)
<div class="field"><label for="questions-{{ $index }}-options-{{ $option }}">Soal {{ $index + 1 }} · opsi {{ chr(65 + $option) }}</label><input id="questions-{{ $index }}-options-{{ $option }}" name="questions[{{ $index }}][options][{{ $option }}]" value="{{ old('questions.'.$index.'.options.'.$option,$question['options'][$option]) }}" maxlength="500" required></div>
@endforeach
<div class="field"><label for="questions-{{ $index }}-correct">Kunci soal {{ $index + 1 }}</label><select id="questions-{{ $index }}-correct" name="questions[{{ $index }}][correct]" required><option value="">Pilih jawaban benar</option>
@foreach(range(0,3) as $option)
<option value="{{ $option }}" @selected((string)old('questions.'.$index.'.correct',$question['correct']) === (string)$option)>{{ chr(65 + $option) }}</option>
@endforeach
</select></div></fieldset>
@endforeach
</div>
