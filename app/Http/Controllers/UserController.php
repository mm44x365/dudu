<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private $perPage = 6;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $users = [];
        if ($request->get('keyword')) {
            $users = User::search($request->keyword)->paginate($this->perPage);
        } else {
            $users =  User::paginate($this->perPage);
        }
        return view('users.index', [
            'users' => $users->appends(['keyword' => $request->keyword])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator  = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:30',
                'role' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6|confirmed'
            ],
            [],
            $this->attributes()
        );

        if ($validator->fails()) {
            $request['role'] = Role::select('id', 'name')->find($request->role);
            return redirect()->back()->withInput($request->all())->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            $user =  User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);
            $user->assignRole($request->role);
            Alert::toast(trans('users.alert.create.message.success'), 'success');
            return redirect()->route('users.index');
        } catch (\Throwable $th) {
            DB::rollBack();
            Alert::toast(trans('users.alert.create.message.error'), 'error');
            $request['role'] = Role::select('id', 'name')->find($request->role);
            return redirect()->back()->withInput($request->all())->withErrors($validator);
        } finally {
            DB::commit();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        return view('users.edit', [
            'user' => $user,
            'roleSelected' => $user->roles->first()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {

        $validator  = Validator::make(
            $request->all(),
            [
                'role' => 'required',
            ],
            [],
            $this->attributes()
        );

        if ($validator->fails()) {
            $request['role'] = Role::select('id', 'name')->find($request->role);
            return redirect()->back()->withInput($request->all())->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            $user->syncRoles($request->role);
            Alert::toast(trans('users.alert.update.message.success'), 'success');
            return redirect()->route('users.index');
        } catch (\Throwable $th) {
            DB::rollBack();
            Alert::toast(trans('users.alert.update.message.error'), 'error');
            $request['role'] = Role::select('id', 'name')->find($request->role);
            return redirect()->back()->withInput($request->all())->withErrors($validator);
        } finally {
            DB::commit();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        DB::beginTransaction();
        try {
            $user->removeRole($user->roles->first());
            $user->delete();
            Alert::toast(trans('users.alert.delete.message.success'), 'success');
        } catch (\Throwable $th) {
            DB::rollBack();
            Alert::toast(trans('users.alert.delete.message.error'), 'error');
        } finally {
            DB::commit();
            return redirect()->back();
        }
    }

    private function attributes()
    {
        return  [
            'name' => trans('users.form_control.input.name.attribute'),
            'role' => trans('users.form_control.select.role.attribute'),
            'email' => trans('users.form_control.input.email.attribute'),
            'password' => trans('users.form_control.input.password.attribute')
        ];
    }
}
