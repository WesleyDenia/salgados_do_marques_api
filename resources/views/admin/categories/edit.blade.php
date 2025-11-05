@extends('admin.layout')

@section('title', 'Editar categoria')

@section('content')
  <div class="card">
    <h2 style="margin-top:0;">Editar categoria</h2>
    @include('admin.categories.form')
  </div>
@endsection
