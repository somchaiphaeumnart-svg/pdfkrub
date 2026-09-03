@extends('layouts.app')
@section('title', 'cookies')
@section('content')
<div class='max-w-4xl mx-auto px-4 py-20 text-center'>
    <h1 class='text-3xl font-bold text-gray-800 mb-4'>cookies</h1>
    <p class='text-gray-500'>หน้านี้กำลังอยู่ในขั้นตอนการพัฒนา</p>
    <a href='{{ route("home") }}' class='btn-ghost px-6 py-2.5 rounded-xl text-sm mt-6 inline-block'>กลับหน้าแรก</a>
</div>
@endsection
