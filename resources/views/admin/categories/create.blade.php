@extends('admin.layout')

@section('title', 'Nova categoria')

@section('content')
  <div class="card">
    <h2 style="margin-top:0;">Nova categoria</h2>
    @include('admin.categories.form')
  </div>
@endsection
