@extends('admin.layout')

@section('title', 'Nova lista de preços')

@section('content')
  <div class="card"><h2 style="margin-top:0;">Nova lista de preços</h2>@include('admin.price-lists.form')</div>
@endsection
