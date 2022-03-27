<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Notification;
use App\Model\NotificationUser;
use DB;
use Response;
use Auth;

class NotificationController extends Controller
{
  public function get_notif()
  {
    
    $user=DB::table('notification_type_users')->where('user_id', auth()->id())->pluck('notification_type_id')->toArray();
    $typ="0";
    foreach ($user as $value) {
      $typ.=",$value";
    }
    $wr="1=1";
    $user=DB::table('users')->where('id', auth()->id())->first();
    if (!$user->is_admin) {
        $wr.=" and y.type in ($typ)";
        $wr.=" and y.company_id = ".auth()->user()->company_id;
    }
    DB::statement("SET lc_time_names = 'id_ID';");
    $user_id = Auth::user()->id;
    $sql="
    select * from
    (
    select id,title,des,url,params,type,date,company_id from notif_type_17
    union
    select id,title,des,url,params,type,date,company_id from notif_type_5
    union
    select id,title,des,url,params,type,date,company_id from notif_type_6
    union
    select id,title,des,url,params,type,date,company_id from notif_type_7
    union
    select id,title,des,url,params,type,date,company_id from notif_type_8
    union
    select id,title,des,url,params,type,date,company_id from notif_type_11
    union
    select id,title,des,url,params,type,date,company_id from notif_type_12
    union
    select id,title,des,url,params,type,date,company_id from notif_type_13
    union
    select id,title,des,url,params,type,date,company_id from notif_type_14
    union
    select id,title,des,url,params,type,date,company_id from notif_type_16
    union
    select id,title,des,url,params,type,date,company_id from notif_type_10
    ) as y
    where $wr AND `id` NOT IN (SELECT notification_id FROM noticed_notifications WHERE user_id = $user_id AND `type` = y.type) 
    order by y.date desc
    ";
    //echo($sql);die;
    $item=DB::select($sql);

    // $items = DB::table('notification_users')
    //     ->leftJoin('notifications','notifications.id','=','notification_users.notification_id')
    //     ->whereRaw('notification_users.is_read = 0')
    //     ->whereRaw('notification_users.user_id = '.auth()->id())
    //     ->selectRaw('notifications.name as `title`, notifications.route as `url`, '
    //         . 'notifications.description as `desc`, '
    //         . 'notifications.parameter as `params`, '
    //         . 'notifications.created_at as `date`,'
    //         . 'notification_users.id')
    //     ->get();
    //
    // foreach($items as $index => $item){
    //     $items[$index]->params = json_decode($item->params);
    // }

    return Response::json($item,200,[],JSON_NUMERIC_CHECK);
  }

  public function detail_notification()
  {
    $whereNotifJO = "((notifications.notification_type_id = 6 or notifications.notification_type_id = 7 or notifications.notification_type_id = 8) and notifications.is_done = 0)";
    $whereNotifNonJO = "((notifications.notification_type_id <> 6 and notifications.notification_type_id <> 7 and notifications.notification_type_id <> 8) and notification_users.is_read = 0)";
    $cWhere = " {$whereNotifJO} or {$whereNotifNonJO} ";
    $data['notification']=DB::table('notification_users')
        ->leftJoin('notifications','notifications.id','=','notification_users.notification_id')
        ->where('notification_users.user_id', auth()->id())
        ->whereRaw($cWhere)
        ->select('notifications.*','notification_users.id as user_notif_id')
        ->groupBy('notification_users.notification_id')
        ->orderBy('notifications.created_at','desc')
        ->limit(30)
        ->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  public function view_notif(Request $request)
  {
    // dd($request);
    // DB::beginTransaction();
    // NotificationUser::find($request->user_notif_id)->update([
    //   'is_read' => 1
    // ]);
    // DB::commit();
    // $dt=DB::table('notification_users')
    // ->leftJoin('notifications','notifications.id','=','notification_users.notification_id')
    // ->where('notification_users.id', $request->user_notif_id)
    // ->whereRaw('notifications.is_done = 0 and notification_users.is_read = 0')
    // ->select('notifications.*','notification_users.id as user_notif_id')
    // ->first();
    DB::table('noticed_notifications')->insert([
        'notification_id' => $request->id,
        'type' => $request->type,
        'user_id' => Auth::user()->id
    ]);
    return Response::json($request, 200, [], JSON_NUMERIC_CHECK);
  }
}
