@extends("mail.layout")

@section("title")
Welcome to {{ env("WEB_NAME") }}
@endsection

@section("content")
<p>
    Hello, {{ $data['first_name'] }}! <br />
    Welcome to {{ env("WEB_NAME") }}. <br /> <br />
    Your account has been activated and you can now <a href="{{ env("WEB_URL") }}/auth/login" target="_blank">log in</a>
</p>
@endsection

