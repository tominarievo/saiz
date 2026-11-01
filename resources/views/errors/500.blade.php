@extends('errors::illustrated-layout')

@section('code', '500')
@section('title', __('Error'))

@if(\Illuminate\Support\Str::startsWith($exception->getMessage(), '[ERROR]'))
  @section('message', str_replace('[ERROR]', '', $exception->getMessage()))
@else
  @section('message', "システムエラーが発生しました。")
@endif