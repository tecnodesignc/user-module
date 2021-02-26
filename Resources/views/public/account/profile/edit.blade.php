@extends('layouts.master')
@section('title')
    {{ trans('user::auth.profile') }} | @parent
@stop

@section('content')
    <div class="container">
        <div class="container bootstrap snippet">
            <div class="row">
                <div class="col-sm-10"><h1> {{ trans('user::users.title.edit-profile') }}</h1></div>
                <div class="col-sm-2"><a href="/users" class="pull-right"><img title="profile image" class="img-circle img-responsive" src="http://www.gravatar.com/avatar/28fd20ccec6865e2d5f0e1f4446eb7bf?s=100"></a></div>
            </div>
            {!! Form::open(['route' => ['account.profile.update'], 'method' => 'put']) !!}
            <div class="row">
                <div class="col-sm-3"><!--left col-->
                    <div class="text-center">
                        <img src="http://ssl.gstatic.com/accounts/ui/avatar_2x.png" class="avatar img-circle img-thumbnail" alt="avatar">

                    </div></hr><br>

                </div><!--/col-3-->
                <div class="col-sm-9">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#account_tab" data-toggle="tab">{{ trans('user::users.tabs.data') }}</a></li>
                        <li class=""><a href="#password_tab" data-toggle="tab">{{ trans('user::users.tabs.new password') }}</a></li>
                    </ul>


                    <div class="tab-content">
                        <div class="tab-pane active" id="home">
                            <hr>
                            <form class="form" action="##" method="post" id="registrationForm">
                                <div class="form-group">

                                    <div class="col-xs-6">
                                        <label for="first_name"><h4>First name</h4></label>
                                        <input type="text" class="form-control" name="first_name"  value="{{$currentUser->first_name}}" id="first_name" placeholder="first name" title="enter your first name if any.">
                                    </div>
                                </div>
                                <div class="form-group">

                                    <div class="col-xs-6">
                                        <label for="last_name"><h4>Last name</h4></label>
                                        <input type="text" class="form-control" name="last_name" id="last_name"  value="{{$currentUser->last_name}}" placeholder="last name" title="enter your last name if any.">
                                    </div>
                                </div>

                                <div class="form-group">

                                    <div class="col-xs-6">
                                        <label for="phone"><h4>Phone</h4></label>
                                        <input type="text" class="form-control" name="fields.phone" id="phone" value="{{$currentUser->fields->phone??''}}"  placeholder="enter phone" title="enter your phone number if any.">
                                    </div>
                                </div>
                                <div class="form-group">

                                    <div class="col-xs-6">
                                        <label for="email"><h4>Email</h4></label>
                                        <input type="email" class="form-control" name="email" id="email" value="{{$currentUser->email}}" disabled  placeholder="you@email.com" title="enter your email.">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-xs-12">
                                        <br>
                                        <button class="btn btn-lg btn-success" type="submit"><i class="glyphicon glyphicon-ok-sign"></i> Save</button>
                                        <button class="btn btn-lg" type="reset"><i class="glyphicon glyphicon-repeat"></i> Reset</button>
                                    </div>
                                </div>
                            </form>

                            <hr>

                        </div><!--/tab-pane-->
                        <div class="tab-pane" id="settings">
                                <div class="form-group">
                                    <div class="col-xs-6">
                                        <label for="password"><h4>Password</h4></label>
                                        <input type="password" class="form-control" name="password" id="password" placeholder="password" title="enter your password.">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-xs-6">
                                        <label for="password2"><h4>Verify</h4></label>
                                        <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" placeholder="password2" title="enter your password2.">
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div><!--/tab-pane-->
                </div><!--/tab-content-->

            </div><!--/col-9-->
            {!! Form::close() !!}
        </div><!--/row-->
    </div>
@stop
@section('scrips')
    @parent

@stop
