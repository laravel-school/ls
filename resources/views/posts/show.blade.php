@extends('app')

@section('content')
    <h1>{{ $post['title'] }}</h1>
    <p><small>By {{ $post['author'] }} on {{ $post['date'] }}</small></p>
    <div>{!! $post['content'] !!}</div>
    <a href="{{ route('posts.index') }}">← Back to Blog</a>
@endsection
