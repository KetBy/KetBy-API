@extends("mail.layout")

@section("title")
Your {{ env("WEB_NAME") }} password has been changed
@endsection

@section("content")
<p>
    Hello! This is to let you know that your {{ env("WEB_NAME") }} has been changed. If you don't remember changing it yourself, please rest your account's password <a href="{{ env("WEB_URL") }}/auth/password">here</a> as soon a possible!
</p>
@endsection