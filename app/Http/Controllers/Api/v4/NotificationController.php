<?php

namespace App\Http\Controllers\Api\v4;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Notification;
use App\Model\NotificationUser;
use DB;
use Response;
use Auth;

class NotificationController extends Controller
{
  public function get_notif(Request $request)
  {
    // $item = NotificationUser::with('notification')
    //     ->where('user_id', auth()->id())
    //     ->where('is_read', 0)
    //     ->orderBy('created_at','desc')
    //     ->get();
    // $whereNotifJO = "((notifications.notification_type_id = 6 or notifications.notification_type_id = 7 or notifications.notification_type_id = 8) and notifications.is_done = 0)";
    // $whereNotifNonJO = "((notifications.notification_type_id <> 6 and notifications.notification_type_id <> 7 and notifications.notification_type_id <> 8) and notification_users.is_read = 0)";
    // $cWhere = " {$whereNotifJO} or {$whereNotifNonJO} ";
    //echo($cWhere);
    // $data=DB::table('notification_users')
    //     ->leftJoin('notifications','notifications.id','=','notification_users.notification_id')
    //     ->where('notification_users.user_id', auth()->id())
    //     ->whereRaw($cWhere)
    //     ->select('notifications.*','notification_users.id as user_notif_id')
    //     ->groupBy('notification_users.notification_id');
        // ->limit(5)
        // ->get();

    $user=DB::table('notification_type_users')->where('user_id', auth()->id())->pluck('notification_type_id')->toArray();
    $typ="0";
    foreach ($user as $value) {
      $typ.=",$value";
    }
    $wr="y.type in ($typ)";
    $user=DB::table('users')->where('id', auth()->id())->first();
    if (!$user->is_admin) {
      $wr." and y.company_id = ".auth()->user()->company_id;
    }
    DB::statement("SET lc_time_names = 'id_ID';");
    $user_id = Auth::user()->id;
    $start = $request->start ?? 0;
    $length = $request->length ?? 5;
    $sql="
    select * from
    (
    select id,title,des,date,type from notif_type_5
    union
    select id,title,des,date,type from notif_type_6
    union
    select id,title,des,date,type from notif_type_7
    union
    select id,title,des,date,type from notif_type_8
    union
    select id,title,des,date,type from notif_type_11
    union
    select id,title,des,date,type from notif_type_12
    union
    select id,title,des,date,type from notif_type_13
    union
    select id,title,des,date,type from notif_type_14
    union
    select id,title,des,date,type from notif_type_16
    union
    select id,title,des,date,type from notif_type_10
    ) as y
    where $wr AND `id` NOT IN (SELECT notification_id FROM noticed_notifications WHERE user_id = $user_id AND `type` = y.type) 
    order by y.date desc
    ";
    //echo($sql);die;
    $count_item=count(DB::select($sql));
    $sql="
    select * from
    (
    select id,title,des,date,type from notif_type_5
    union
    select id,title,des,date,type from notif_type_6
    union
    select id,title,des,date,type from notif_type_7
    union
    select id,title,des,date,type from notif_type_8
    union
    select id,title,des,date,type from notif_type_11
    union
    select id,title,des,date,type from notif_type_12
    union
    select id,title,des,date,type from notif_type_13
    union
    select id,title,des,date,type from notif_type_14
    union
    select id,title,des,date,type from notif_type_16
    union
    select id,title,des,date,type from notif_type_10
    ) as y
    where $wr AND `id` NOT IN (SELECT notification_id FROM noticed_notifications WHERE user_id = $user_id AND `type` = y.type) 
    order by y.date desc
    limit $start, $length
    ";
    $item = DB::select($sql);
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
    $data['status'] = 'OK'; 
    $data['grandtotal'] = $count_item; 
    $data['total'] = count($item); 
    $data['notification'] = $item; 
    return Response::json($data,200,[],JSON_NUMERIC_CHECK);
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
    if(!$request->filled('id')) {
        return Response::json(['status' => 'ERROR', 'message' => 'ID Notifikasi tidak boleh kosong!', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }

    if(!$request->filled('type')) {
        return Response::json(['status' => 'ERROR', 'message' => 'Tipe Notifikasi tidak boleh kosong!', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }
    $user_id = auth()->id();
    $sql = "select * from
    (
    select id,title,des,date,type from notif_type_5
    union
    select id,title,des,date,type from notif_type_6
    union
    select id,title,des,date,type from notif_type_7
    union
    select id,title,des,date,type from notif_type_8
    union
    select id,title,des,date,type from notif_type_11
    union
    select id,title,des,date,type from notif_type_12
    union
    select id,title,des,date,type from notif_type_13
    union
    select id,title,des,date,type from notif_type_14
    union
    select id,title,des,date,type from notif_type_16
    union
    select id,title,des,date,type from notif_type_10
    ) as y
    where y.id = {$request->id} AND y.type = {$request->type}";
    $notifications = DB::select($sql);
    if(count($notifications) == 0) {
        return Response::json(['status' => 'ERROR', 'message' => 'Notifikasi tidak ditemukan!', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }
    DB::table('noticed_notifications')->insert([
        'notification_id' => $request->id,
        'type' => $request->type,
        'user_id' => Auth::user()->id
    ]);
    $data['status'] = 'OK';
    $data['message'] = 'Notifikasi telah dibaca';
    $data['notification'] = $notifications[0];
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
}
