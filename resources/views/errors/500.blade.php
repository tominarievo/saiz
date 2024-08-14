@extends('errors::illustrated-layout')

@section('code', '500')
@section('title', __('Error'))

@if(starts_with($exception->getMessage(), '[ERROR]'))
  @section('message', str_replace('[ERROR]', '', $exception->getMessage()))
@else
  @section('message', "システムエラーが発生しました。")
@endif