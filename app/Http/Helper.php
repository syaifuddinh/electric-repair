<?php
use App\User;
use App\Model\Role;
use App\Model\Company;
use App\Model\Rate;
use App\Model\Account;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/*
      Date : 13-03-2020
      Description : Menampilkan tanggal dalam format human-readable
      Developer : Didin
      Status : Create
*/
function fullDate($time) {
  if($time != null) {
        $d = calc('d', $time);
        $m = (int )calc('m', $time) - 1;
        $Y = calc('Y', $time);
        $months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        return $d . ' ' . $months[$m] . ' ' . $Y;
  }
}

/*
      Date : 13-03-2020
      Description : Memformat tanggal
      Developer : Didin
      Status : Create
*/
function calc($format, $time) {
    return date($format, strtotime($time));
}

function shutUp($var){
  try {
    return $var;
  } catch (\Exception $e) {
    return null;
  }
}

function formatPrice($number){
  return "Rp. ".number_format($number, 0, '.',',');
}

function formatNumber($number){
  if ($number<0) {
    return '('.number_format(abs($number), 2, '.',',').')';
  } elseif ($number>=0) {
    return number_format($number, 2, '.',',');
  } else {
    return 0;
  }
}

function companyAdmin($iduser)
{
  $user=DB::table('users')->where('id', $iduser)->first();
  if ($user->is_admin) {
    $data=DB::table('companies')
    ->select('id', 'code', 'name')
    ->get();
  } else {
    $data=DB::table('companies')->where('id', $user->company_id)
    ->select('id', 'code', 'name')
    ->get();
  }
  return $data;
}

function cekCashCount($company_id,$account_id)
{
  $acc=Account::whereRaw("company_id = $company_id and no_cash_bank = 1 and is_freeze = 1")->select('id')->get();
  $kelompok=[];
  foreach ($acc as $key => $value) {
    $kelompok[]=$value->id;
  }
  if (in_array($account_id,$kelompok)) {
    return true;
  } else {
    return false;
  }
}

function like_match($pattern, $subject)
{
    $pattern = str_replace('%', '.*', preg_quote($pattern, '/'));
    return (bool) preg_match("/^{$pattern}$/i", $subject);
}

function userRoles($iduser)
{
  $sql = "SELECT roles.slug FROM user_roles LEFT JOIN roles ON roles.id = user_roles.role_id WHERE user_roles.user_id = ".$iduser;
  $run = DB::select($sql);
  $data=[];
  foreach ($run as $key => $value) {
    $data[]=$value->slug;
  }
  return json_encode($data);
}

function userProfile($iduser) {
  $jsn=User::with('company')->where('id', $iduser)->first();
  return $jsn->toJson();
}

function dateDB($date){
  if($date) {
      return date('Y-m-d', strtotime($date));
  } else {
      return date('Y-m-d');
  }
}

function menjorok($count){
  $str = "";
  for ($i=0; $i < $count; $i++) {
    $str .= "&nbsp;";
  }
  return $str;
}
function menjorokSpasi($count){
  $str = "";
  for ($i=0; $i < $count; $i++) {
    $str .= "  ";
  }
  return $str;
}

function company_alls()
{
  return Company::orderBy('name','asc')->get();
}

function createTimestamp($date, $time){
  if($date) {
      $split = explode("-",$date);
      $split_tm = explode(":",$time);
      return Carbon::create($split[2],$split[1],$split[0],$split_tm[0],$split_tm[1],0,'Asia/Jakarta');
  } else {
      return null;
  }
}

function ratePriceGen($idasal,$idtujuan,$tipe1,$tipe2)
{
  if (empty($idasal) || empty($idtujuan)) {
    return 0;
  }
  if ($tipe1==1 && $tipe2==1) {
    $r = Rate::where('dock_awal', $idasal)->where('dock_tujuan', $idtujuan)->first();
  } elseif ($tipe1==1 && $tipe2==2) {
    $r = Rate::where('dock_awal', $idasal)->where('depo_tujuan', $idtujuan)->first();
  } elseif ($tipe1==2 && $tipe2==1) {
    $r = Rate::where('depo_awal', $idasal)->where('dock_tujuan', $idtujuan)->first();
  } else {
    $r = Rate::where('depo_awal', $idasal)->where('depo_tujuan', $idtujuan)->first();
  }
  if (isset($r)) {
    return $r->biaya;
  } else {
    return 0;
  }
}

function dateNowView(){
  return Carbon::now('Asia/Jakarta')->format('d-m-Y');
}

function dateNowDB(){
  return Carbon::now('Asia/Jakarta')->format('Y-m-d');
}

function dateApiMobile($time){
  if (isset($time)) {
    return Carbon::parse($time)->format('m/d/Y h:i:s A');
  } else {
    return null;
  }
}

function dateFullTime($time){
  if (isset($time)) {
    return Carbon::parse($time)->format('d-m-Y H:i:s');
  } else {
    return '-';
  }
}


//aslinya menit
function convertSecs($time, $format = '%02d Jam %02d Menit') {
  if ($time < 1) {
      return '0 Menit';
  }
  $bagi = floor($time / 60);
  $hours = floor($bagi / 60);
  $minutes = ($bagi % 60);
  return sprintf($format, $hours, $minutes);
}

function convertSecs2($secs) {
    if (empty($secs)) {
      $seconds=0;
    } else {
      $seconds=$secs;
    }
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");
    // dd($dtF->diff($dtT)->format('%d'));
    return $dtF->diff($dtT)->format('%d').' Hari';
    if ($dtF->diff($dtT)->format('%h') < 1 && $dtF->diff($dtT)->format('%d') < 1) {
      return $dtF->diff($dtT)->format('%i Menit');
    } else if ($dtF->diff($dtT)->format('%d') < 1) {
      return $dtF->diff($dtT)->format('%h Jam %i Menit');
    } else {
      return $dtF->diff($dtT)->format('%d Hari %h Jam %i Menit');
    }
}

function dateView($date, $text = false){
  if (empty($date)) {
    return "-";
  } else {
    if($text) return date('d F Y', strtotime($date));
    return date('d-m-Y', strtotime($date));
  }
}

function auths(){
  return Auth::id();
}

function gotRole($slug = "dashboard", $user){
  $role = array();
  $user = User::find($user);
  $roles = $user->roles;
  foreach ($roles as $key => $value) {
    $role_class = Role::find($value->role_id);
    $role[] = $role_class->slug;
  }
  if (in_array($slug,$role) || $user->super_admin == 1) {
    return true;
  } else {
    return false;
  }
}

function swals($title, $message, $type = 'success'){
  $data = [
    'title' => $title,
    'message' => $message,
    'type' => $type,
  ];
  return json_encode($data);
}

function distanceHitungRaw($lat1, $lon1, $lat2, $lon2) {

  $theta = $lon1 - $lon2;
  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
  $dist = acos($dist);
  $dist = rad2deg($dist);
  $miles = $dist * 60 * 1.1515;

  return round(($miles * 1.609344), 3);
}

function make_comparer() {
  $criteria = func_get_args();
  foreach ($criteria as $index => $criterion) {
    $criteria[$index] = is_array($criterion)
        ? array_pad($criterion, 3, null)
        : array($criterion, SORT_ASC, null);
  }

  return function($first, $second) use ($criteria) {
    foreach ($criteria as $criterion) {
      list($column, $sortOrder, $projection) = $criterion;
      $sortOrder = $sortOrder === SORT_DESC ? -1 : 1;

      if ($projection) {
        $lhs = call_user_func($projection, $first[$column]);
        $rhs = call_user_func($projection, $second[$column]);
      }
      else {
        $lhs = $first[$column];
        $rhs = $second[$column];
      }

      if ($lhs < $rhs) {
        return -1 * $sortOrder;
      }
      else if ($lhs > $rhs) {
        return 1 * $sortOrder;
      }
    }

    return 0; // tiebreakers exhausted, so $first == $second
  };
}

function penyebut($nilai) {
    $nilai = abs($nilai);
    $huruf = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
    $temp = "";
    if ($nilai < 12) {
        $temp = " ". $huruf[$nilai];
    } else if ($nilai <20) {
        $temp = penyebut($nilai - 10). " belas";
    } else if ($nilai < 100) {
        $temp = penyebut($nilai/10)." puluh". penyebut($nilai % 10);
    } else if ($nilai < 200) {
        $temp = " seratus" . penyebut($nilai - 100);
    } else if ($nilai < 1000) {
        $temp = penyebut($nilai/100) . " ratus" . penyebut($nilai % 100);
    } else if ($nilai < 2000) {
        $temp = " seribu" . penyebut($nilai - 1000);
    } else if ($nilai < 1000000) {
        $temp = penyebut($nilai/1000) . " ribu" . penyebut($nilai % 1000);
    } else if ($nilai < 1000000000) {
        $temp = penyebut($nilai/1000000) . " juta" . penyebut($nilai % 1000000);
    } else if ($nilai < 1000000000000) {
        $temp = penyebut($nilai/1000000000) . " milyar" . penyebut(fmod($nilai,1000000000));
    } else if ($nilai < 1000000000000000) {
        $temp = penyebut($nilai/1000000000000) . " trilyun" . penyebut(fmod($nilai,1000000000000));
    }
    return $temp;
}
?>
