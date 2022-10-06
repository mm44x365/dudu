@extends('layouts.dashboard')
@section('title')
    {{ trans('users.title.create') }}
@endsection
@section('breadcrumbs')
    {{ Breadcrumbs::render('add_user') }}
@endsection
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form action="" method="POST">
                        <!-- name -->
                        <div class="form-group">
                            <label for="input_user_name" class="font-weight-bold">
                                {{ trans('users.form_control.input.name.label') }}
                            </label>
                            <input id="input_user_name" value="" name="name" type="text" class="form-control"
                                placeholder="{{ trans('users.form_control.input.name.placeholder') }}" />
                            <!-- error message -->
                        </div>
                        <!-- role -->
                        <div class="form-group">
                            <label for="select_user_role" class="font-weight-bold">
                                {{ trans('users.form_control.select.role.label') }}
                            </label>
                            <select id="select_user_role" name="role"
                                data-placeholder="{{ trans('users.form_control.select.role.placeholder') }}"
                                class="custom-select w-100">
                                <option value="" selected="selected">Role</option>
                            </select>
                            <!-- error message -->
                        </div>
                        <!-- email -->
                        <div class="form-group">
                            <label for="input_user_email" class="font-weight-bold">
                                {{ trans('users.form_control.input.email.label') }}
                            </label>
                            <input id="input_user_email" value="" name="email" type="email" class="form-control"
                                placeholder=" {{ trans('users.form_control.input.email.placeholder') }}"
                                autocomplete="email" />
                            <!-- error message -->
                        </div>
                        <!-- password -->
                        <div class="form-group">
                            <label for="input_user_password" class="font-weight-bold">
                                {{ trans('users.form_control.input.password.label') }}
                            </label>
                            <input id="input_user_password" name="password" type="password" class="form-control"
                                placeholder="{{ trans('users.form_control.input.password.placeholder') }}"
                                autocomplete="new-password" />
                            <!-- error message -->
                        </div>
                        <!-- password_confirmation -->
                        <div class="form-group">
                            <label for="input_user_password_confirmation" class="font-weight-bold">
                                {{ trans('users.form_control.input.password_confirmation.label') }}
                            </label>
                            <input id="input_user_password_confirmation" name="password_confirmation" type="password"
                                class="form-control"
                                placeholder="{{ trans('users.form_control.input.password_confirmation.placeholder') }}"
                                autocomplete="new-password" />
                            <!-- error message -->
                        </div>
                        <div class="float-right">
                            <a class="btn btn-warning px-4 mx-2" href="">
                                {{ trans('users.button.back.value') }}
                            </a>
                            <button type="submit" class="btn btn-primary float-right px-4">
                                {{ trans('users.button.save.value') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
