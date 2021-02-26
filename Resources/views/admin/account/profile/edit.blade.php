@extends('layouts.master')

@section('content-header')
    <h1>
        {{ trans('user::users.title.edit-profile') }}
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('dashboard.index') }}"><i class="fa fa-dashboard"></i> {{ trans('core::core.breadcrumb.home') }}</a></li>
        <li class="active">{{ trans('user::users.breadcrumb.edit-profile') }}</li>
    </ol>
@stop

@section('content')
    {!! Form::open(['route' => ['admin.account.profile.update'], 'method' => 'put']) !!}
    <div class="row">
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#account_tab" data-toggle="tab">{{ trans('user::users.tabs.data') }}</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="account_tab">
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-4">
                                    @mediaSingle('mainimage',$user)
                                    <div class='form-group{{ $errors->has("fields.facebook") ? ' has-error' : '' }}'>
                                        {!! Form::label("fields[summary]", 'Facebook') !!}
                                        <?php $old =$user->fields->facebook??'' ?>
                                        {!! Form::text("fields[facebook]", old("fields.facebook", $old), ['class' => 'form-control','rows'=>2, 'placeholder' => 'Facebook']) !!}
                                        {!! $errors->first("fields.facebook", '<span class="help-block">:message</span>') !!}
                                    </div>
                                    <div class='form-group{{ $errors->has("fields.twitter") ? ' has-error' : '' }}'>
                                        {!! Form::label("fields[twitter]", 'Twitter') !!}
                                        <?php $old =$user->fields->twiter??'' ?>
                                        {!! Form::text("fields[twiter]", old("fields.twitter", $old), ['class' => 'form-control','rows'=>2, 'placeholder' => 'Twitter']) !!}
                                        {!! $errors->first("fields.twitter", '<span class="help-block">:message</span>') !!}
                                    </div>
                                    <div class='form-group{{ $errors->has("fields.instagram") ? ' has-error' : '' }}'>
                                        {!! Form::label("fields[instagram]", 'Instagram') !!}
                                        <?php $old =$user->fields->instagram??'' ?>
                                        {!! Form::text("fields[instagram]", old("fields.twitter", $old), ['class' => 'form-control','rows'=>2, 'placeholder' => 'Instagram']) !!}
                                        {!! $errors->first("fields.instagram", '<span class="help-block">:message</span>') !!}
                                    </div>
                                    <div class='form-group{{ $errors->has("fields.linkedin") ? ' has-error' : '' }}'>
                                        {!! Form::label("fields[linkedin]", 'Linkedin') !!}
                                        <?php $old =$user->fields->linkedin??'' ?>
                                        {!! Form::text("fields[linkedin]", old("fields.linkedin", $old), ['class' => 'form-control','rows'=>2, 'placeholder' => 'Linkedin']) !!}
                                        {!! $errors->first("fields.linkedin", '<span class="help-block">:message</span>') !!}
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <?php $old = $user->fields->bio?? '' ?>
                                    <div class='form-group{{ $errors->has("fields.bio") ? ' has-error' : '' }}'>
                                        @editor('bio', trans('user::users.form.bio'), old("fields.bio", $old), 'fields')
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary btn-flat">{{ trans('core::core.button.update') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {!! Form::close() !!}
@stop
@section('footer')
    <a data-toggle="modal" data-target="#keyboardShortcutsModal"><i class="fa fa-keyboard-o"></i></a> &nbsp;
@stop
@section('shortcuts')
@stop

@push('js-stack')
    <script>
        $( document ).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();
            $('input[type="checkbox"].flat-blue, input[type="radio"].flat-blue').iCheck({
                checkboxClass: 'icheckbox_flat-blue',
                radioClass: 'iradio_flat-blue'
            });
        });
    </script>
@endpush
