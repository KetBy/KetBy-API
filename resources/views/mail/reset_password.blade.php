@extends("mail.layout")

@section("title")
Reset your {{ env("WEB_NAME") }} password
@endsection

@section("content")
<p>
    Please open this link in order to reset your {{ env("WEB_NAME") }} password:
    <a href="{{ env("WEB_URL") }}/auth/password/{{ $data['token'] }}" target="_blank">{{ env("WEB_URL") }}/auth/password/{{ $data['token'] }}</a>
</p>
@endsection