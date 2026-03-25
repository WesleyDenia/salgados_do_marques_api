@extends('admin.layout')

@section('title', 'Novo Parceiro')

@section('content')
  <div class="card">
    <h2 style="margin-top:0;">Novo parceiro</h2>
    @include('admin.partners.form')
  </div>
@endsection
