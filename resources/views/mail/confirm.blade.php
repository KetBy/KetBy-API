@extends("mail.layout")

@section("title")
Please confirm your {{ env("WEB_NAME") }} account
@endsection

@section("content")
<p>
    Please open this link in order to activate your {{ env("WEB_NAME") }} account:
    <a href="{{ env("WEB_URL") }}/auth/confirm/{{ $data['token'] }}" target="_blank">{{ env("WEB_URL") }}/auth/confirm/{{ $data['token'] }}</a>
</p>
@endsection
