<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Model\Company;
use App\Model\Contact;
use App\Model\City;
use App\Model\Role;
use App\Model\GroupType;
use App\Model\GroupRole;
use App\Model\UserRole;
use App\Model\NotificationTypeUser;
use App\Model\Notification;
use App\Abstracts\Setting\User AS US;
use DB;
use Response;
use Exception;
use App\Abstracts\Setting\Role AS Previlage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $dt = DB::table('users')
      ->whereIsActive(1)
      ->select('id', 'name')
      ->get();
      $data = [];
      $data['message'] = 'OK';
      $data['data'] = $dt;
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['company'] = companyAdmin(auth()->id());
      $data['city'] = City::all();
      $data['group'] = GroupType::all();
      $data['contact'] = Contact::all();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      // dd($request);
      $request->validate([
        'name' => 'required',
        'email' => 'required|email|unique:users',
        'password' => 'required|same:password_confirmation',
        'password_confirmation' => 'required',
        'company_id' => 'required',
        'username' => 'required|unique:users',
        'group_id' => 'required',
        'is_admin' => 'required'
      ], [
          'name.required' => 'Nama tidak boleh kosong',
          'email.required' => 'Email tidak boleh kosong',
          'password.required' => 'Password tidak boleh kosong',
          'password.same' => 'Password tidak cocok dengan password konfirmasi',
          'password_confirmation.required' => 'Password konfirmasi tidak boleh kosong',
          'company_id.required' => 'Cabang tidak boleh kosong',
          'username.required' => 'Username tidak boleh kosong',
          'group_id.required' => 'Group user tidak boleh kosong',
      ]);

      DB::beginTransaction();
      $user=User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => bcrypt($request->password),
        'api_token' => str_random(150),
        'company_id' => $request->company_id,
        'username' => $request->username,
        'pass_text' => $request->password,
        'group_id' => $request->group_id,
        'contact_id' => $request->contact_id,
        'city_id' => $request->city_id,
        'is_admin' => $request->is_admin,
      ]);

      $role = GroupRole::where('group_type_id', $request->group_id)->orderBy('role_id','ASC')->get();
      foreach ($role as $key => $value) {
        $user_roles = new UserRole;
        $user_roles->user_id = $user->id;
        $user_roles->role_id = $value->role_id;
        $user_roles->save();
      }

      DB::commit();

      return Response::json(null);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $u = User::with('company')->where('id', $id)->select('users.*')->first();
      return Response::json($u, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      $data['company'] = companyAdmin(auth()->id());
      $data['city'] = City::all();
      $data['item'] = User::find($id);
      $data['group'] = GroupType::all();
      $data['contact'] = Contact::all();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // dd($request);
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$id,
            'company_id' => 'required',
            'username' => 'required|unique:users,username,'.$id,
            'group_id' => 'required',
            'is_admin' => 'required',

        ]);
        
        try{
            DB::beginTransaction();
            $user = User::find($id);
            User::find($id)->update([
                'name' => $request->name,
                'email' => $request->email,
                'company_id' => $request->company_id,
                'username' => $request->username,
                'group_id' => $request->group_id,
                'contact_id' => $request->contact_id,
                'is_admin' => $request->is_admin,
            ]);

            if ($request->group_id != $user->group_id) {
                UserRole::where('user_id', $id)->delete();
                $role = GroupRole::where('group_type_id', $request->group_id)
                    ->orderBy('role_id','ASC')
                    ->get();
                foreach ($role as $key => $value) {
                    UserRole::create([
                        'user_id' => $id,
                        'role_id' => $value->role_id
                    ]);
                }
            }
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            return Response::json([
                'error' => true,
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        }

        return Response::json(['message' => 'Data Berhasil Disimpan']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
      DB::beginTransaction();
      try {

          User::find($id)->delete();
          DB::commit();
      } catch(\Exception $e) {
          return response()->json(['message' => 'Data tidak dapat dihapus karena sudah punya transaksi'], 421);
      }

      return Response::json(['message' => 'Data Berhasil Dihapus']);
    }

    /*
      Date : 09-07-2021
      Description : Ubah password 
      Developer : Didin
      Status : Edit
    */
    public function change_password(Request $request, $id)
    {
        $msg = 'OK';
        $status_code = 200;

        DB::beginTransaction();
        try {
            US::changePassword($request->password, $request->password_again, $id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $msg = $e->getMessage();
            $status_code = 421;
        }

        $data = [];
        $data['message'] = $msg;

        return response()->json($data, $status_code);
    }

    public function role($id)
    {

        $data['role'] = Previlage::index($id);
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function roles()
    {
      $data = DB::table('roles')
      ->select('id', 'name')
      ->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function save_role(Request $request, $id)
    {
      // dd($request);
      if (empty($request->role_id)) {
        return Response::json(['message' => 'Tidak ada Previlage yang dipilih!','status' => 'OK'],200);
      }
      DB::beginTransaction();
      $item = UserRole::where('user_id', $id)->whereNotIn('role_id', $request->role_id)->get();
      foreach ($item as $keys => $values) {
            $items = UserRole::find($values->id);
            $items->delete();
      }

      foreach ($request->role_id as $key => $value) {
        $save = UserRole::where('user_id', $id)->where('role_id', $value)->first();
        if (!$save) {
            DB::table('user_roles')->insert([
                'user_id' => $id,
                'role_id' => $value
            ]);
        }
      }
      DB::commit();

      return Response::json(null);
    }

    public function group_previlage($id)
    {
      $ur=GroupRole::where('group_type_id', $id)->get();
      $data['gtype']=GroupType::find($id);
      $data['role']=Role::orderBy('urut','asc')->get();
      $dt=[];
      foreach ($ur as $key => $value) {
        $dt[]=$value->role_id;
      }
      $data['previlage']=$dt;

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_group_previlage(Request $request, $id)
    {
      $deletesave=GroupRole::where('group_type_id','=',$id)
            ->get();

        foreach ($deletesave as $sv) {
          $sv->delete();
        } 

      $usernyfirst = DB::table('users')->select('*')
      ->where('group_id','=',$id)
      ->get();
      foreach ($usernyfirst as $target ) {
        $deletesaveuser=UserRole::where('user_id','=',$target->id)
            ->get();

        foreach ($deletesaveuser as $sv) {
          $sv->delete();
        } 
      }
      
      if (empty($request->role_id)) {
        return Response::json(['message' => 'Tidak ada Previlage yang dipilih!','status' => 'OK'],200);
      }
      DB::beginTransaction();
      $item = GroupRole::where('group_type_id', $id)->whereNotIn('role_id', $request->role_id)->get();
      foreach ($item as $keys => $values) {
        $items = GroupRole::find($values->id);
        $items->delete();
      }



      $userny = DB::table('users')->select('*')
      ->where('group_id','=',$id)
      ->get();

      foreach ($request->role_id as $key => $value) {
         
        $save = GroupRole::where('group_type_id', $id)->where('role_id', $value)->first();
        
        if (empty($save)) {
          $saves = new GroupRole;
          $saves->group_type_id = $id;
          $saves->role_id = $value;
          $saves->save();
        }

         foreach ($userny as $target) {
        // $j=user_role::create([
        //             'user_id' => $target->id,
        //             'role_id' => 30,
        //             'created_at' => $todaydate,
        //             'updated_at' => ,
        //           ]);
        
        $saveuser = UserRole::where('user_id', $target->id)->where('role_id', $value)->first();
        if (empty($saveuser)) {
        $saveuser = new UserRole();
        $saveuser->user_id = $target->id;
        $saveuser->role_id = $value;
        $saveuser->save();
      }
      }
      }
      DB::commit();



      




     

      return Response::json(null);
    }

    public function store_group(Request $request,$id=null)
    {
      DB::beginTransaction();
        if (empty($id)) {
          GroupType::create([
            'name' => $request->name,
            'slug' => ''
          ]);
        } else {
          GroupType::find($id)->update([
            'name' => $request->name,
          ]);
        }
      DB::commit();

      return Response::json(null);
    }

    public function role_array()
    {
      if(auth()->user()->is_admin == 0) {
          $sql = "SELECT roles.slug FROM user_roles LEFT JOIN roles ON roles.id = user_roles.role_id WHERE user_roles.user_id = ".auth()->id();
      } else {
          $sql = 'SELECT roles.slug FROM roles';
      }
      $run = DB::select($sql);
      $data=[];
      foreach ($run as $key => $value) {
        $data[]=$value->slug;
      }
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function notification($id)
    {
      $data['notification_type']=DB::table('notification_types')->get();
      $data['notification_type_user']=DB::table('notification_type_users')->where('user_id', $id)->get();
      // $data['notifList']=Notification::all();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_notification(Request $request, $id)
    {
      DB::beginTransaction();
      foreach ($request->detail as $key => $value) {
        $cek=NotificationTypeUser::where('notification_type_id', $value['notification_type_id'])->where('user_id', $id)->first();
        if (!$cek && $value['value']==1) {
          NotificationTypeUser::create([
            'notification_type_id' => $value['notification_type_id'],
            'user_id' => $id
          ]);
        } elseif ($cek && $value['value']==0) {
          $cek->delete();
        }
      }
      DB::commit();
    }

    public function user_group_delete(int $id)
    {
      DB::beginTransaction();
      GroupType::find($id)->delete();
      DB::commit();
      return Response::json(['message' => 'Data Berhasil Dihapus']);
    }
}
