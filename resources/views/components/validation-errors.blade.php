@if($errors->any())
<div class="server-error" role="alert" tabindex="-1" autofocus>
    <p><strong>Periksa isian berikut.</strong></p>
    <ul>
        @foreach($errors->messages() as $field => $messages)
            <li><a href="#{{ str_replace('.', '-', $field) }}">{{ $messages[0] }}</a></li>
        @endforeach
    </ul>
</div>
@endif
