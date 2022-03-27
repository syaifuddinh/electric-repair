<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\User;
use App\Http\Controllers\Controller;
use App\Model\CashAdvance;
use App\Model\CashAdvanceStatus;
use App\Model\CashTransaction;
use App\Model\Company;
use App\Model\Account;
use App\Model\Contact;
use App\Model\AccountDefault;
use App\Model\Notification;
use App\Model\NotificationUser;
use App\Utils\TransactionCode;
use DB;
use Response;
use Carbon\Carbon;

class KasBonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['hasKasbon'] = false;

        $cas = CashAdvance::whereRaw('status <= 5')
            ->where('employee_id', auth()->user()->contact_id)
            ->get();

        if(!$cas->isEmpty())
            $data['hasKasbon'] = true;

        return Response::json($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['company'] = companyAdmin(auth()->id());
        $user = User::find(auth()->id());
        $data['employee'] = DB::table('contacts')->where('is_pegawai', 1)->select('id','name')->get();
        $data['contact'] = Contact::find($user->contact_id) ;
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

      // return Response::json($request,500,[],JSON_NUMERIC_CHECK);
        $this->validate($request,
            [
                'company_id' => 'required',
                'employee_id' => 'required',
                'date_transaction' => 'required',
                'due_date' => 'required',
                'total_cash_advance' => 'required|numeric|min:1',
                'description' => 'required'
            ],[
                'date_transaction.required' => 'Field [Tanggal] wajib diisi',
                'total_cash_advance.required' => 'Field [Jumlah] wajib diisi',
                'total_cash_advance.min' => 'Field [Jumlah] harus lebih dari 0',
                'description.required' => 'Field [Keperluan] wajib diisi'
            ]
        );

        DB::beginTransaction();

        $code = new TransactionCode($request->company_id, 'kasbon');
        $code->setCode();
        $trx_code = $code->getCode();

        $ca = CashAdvance::create([
            'code' => $trx_code,
            'company_id' => $request->company_id,
            'employee_id' => $request->employee_id,
            'create_by' => auth()->id(),
            'date_transaction' => dateDB($request->date_transaction),
            'due_date' => dateDB($request->due_date),
            'total_cash_advance' => $request->total_cash_advance,
            'description' => $request->description
        ]);

        CashAdvanceStatus::create([
            'header_id' => $ca->id,
            'status' => 1,
            'user_id' => auth()->id()
        ]);

        $notifName = "Request Approval Kasbon";
        $notifDesc = "Request approval kasbon {$ca->code}";
        self::notifyUsers($ca, auth()->user(), $notifName, $notifDesc);

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
        $data['item'] = CashAdvance::with(
                'statusAkhir', 'statuses', 'company', 'employee',
                'creates', 'approves', 'cancels', 'account_cash', 'account_kasbon')
            ->where('id', $id)->first();
        $data['account'] = Account::with('parent')
            ->where('is_base',0)->orderBy('code')
            ->get();
        $data['cash_transaction'] = CashTransaction::find($data['item']->cash_transaction_id);
        $data['cash_transaction_amount'] = DB::table('cash_transaction_details')->whereHeaderId($data['item']->cash_transaction_id)->sum('amount') ?? 0;
        $data['default'] = AccountDefault::first();

        $reapprovedCount = $data['item']->reapprovalsCount();
        $reapprovedBy = "";

        if($reapprovedCount > 0) {
            $userId = CashAdvanceStatus::where('header_id', $id)
                ->where('status', 3)->latest()->first()->user_id;
            $reapprovedBy = User::find($userId)->name;
        }

        $data['reapprovals'] = [
            'count' => $reapprovedCount,
            'by' => $reapprovedBy
        ];
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);

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
        $data['item'] = CashAdvance::find($id);
        $user = User::find(auth()->id());
        $data['employee'] = [ Contact::find($user->contact_id) ];
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
        $request->validate([
            'date_transaction' => 'required',
            'due_date' => 'required',
            'total_cash_advance' => 'required',
        ]);
        DB::beginTransaction();

        CashAdvance::find($id)->update([
            'date_transaction' => dateDB($request->date_transaction),
            'due_date' => dateDB($request->due_date),
            'total_cash_advance' => $request->total_cash_advance,
            'description' => $request->description,
            'status' => 1
        ]);

        CashAdvanceStatus::create([
            'header_id' => $id,
            'status' => 1,
            'user_id' => auth()->id()
        ]);

        DB::commit();
        return Response::json(null);

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
        CashAdvance::find($id)->update();
        DB::commit();

        return Response::json(null);
    }

    /**
     * Approval awal untuk kasbon
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function approve(Request $request, $id)
    {

        $request->validate([
            'total_approve' => 'required',
        ]);

        DB::beginTransaction();
        $ca = CashAdvance::find($id);
        $ca->update([
            'approve_by' => auth()->id(),
            'total_approve' => $request->total_approve,
            'status' => 2,
        ]);
        CashAdvanceStatus::create([
            'header_id' => $id,
            'status' => 2,
            'user_id' => auth()->id()
        ]);

        $notifName = "Request Approval Kasbon Disetujui";
        $notifDesc = "Request approval kasbon {$ca->code} disetujui";
        self::notifyUsers($ca, auth()->user(), $notifName, $notifDesc);

        $receiver = User::find($ca->create_by);
        self::notifyUsers($ca, auth()->user(), $notifName, $notifDesc, $receiver);

        DB::commit();
    }

    /**
     * Aktivasi untuk kasbon
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function activate(Request $request, $id)
    {
        DB::beginTransaction();
        $ca = CashAdvance::find($id);
        $dateNow = Carbon::now()->format('Y-m-d');
        $trDate = $ca->date_transaction;

        if($ca->date_transaction != $dateNow)
            $tr_date = $dateNow;

        $ca->update([
            'status' => 3,
            'date_transaction' => $dateNow,
            'date_opened' => Carbon::now()->format('Y-m-d')
        ]);

        CashAdvanceStatus::create([
            'header_id' => $id,
            'status' => 3,
            'user_id' => auth()->id()
        ]);
        DB::commit();
    }

    /**
     * Approval untuk perpanjangan kasbon
     *
     * @param  int  $id ID Cash Advance
     * @return \Illuminate\Http\Response
     */
    public function reapprove(Request $request, $id)
    {
        $now = Carbon::now();
        DB::beginTransaction();
        $ca = CashAdvance::find($id);
        $ca->update([
            'status' => 3,
            'due_date' => Carbon::now()->addDays(1)->format('Y-m-d')
        ]);
        CashAdvanceStatus::create([
            'header_id' => $id,
            'status' => 3,
            'user_id' => auth()->id()
        ]);

        $receiver = User::find($ca->create_by);
        $notifName = "Request Reapproval Kasbon Disetujui";
        $notifDesc = "Request reapproval kasbon {$ca->code} disetujui";
        self::notifyUsers($ca, auth()->user(), $notifName, $notifDesc, $receiver);

        DB::commit();
    }

    /**
     * Pengajuan approval untuk perpanjangan kasbon
     *
     * @param  int  $id ID Cash Advance
     * @return \Illuminate\Http\Response
     */
    public function reapproval(Request $request, $id)
    {
        $now = Carbon::now();
        DB::beginTransaction();
        $ca = CashAdvance::find($id);
        $ca->update([
            'status' => 4
        ]);
        CashAdvanceStatus::create([
            'header_id' => $id,
            'status' => 4,
            'user_id' => auth()->id()
        ]);

        $notifName = "Request Reapproval Kasbon";
        $notifDesc = "Request reapproval kasbon {$ca->code}";
        self::notifyUsers($ca, auth()->user(), $notifName, $notifDesc);

        DB::commit();

    }

    /**
     * Posting untuk semua kasbon yang kedaluwarsa
     * tetapi statusnya masih aktif
     *
     * @return \Illuminate\Http\Response
     */
    public static function postingKedaluwarsa()
    {
        DB::beginTransaction();

        if(Carbon::now()->format('H:i') < '14:00') {
            $yesterday = Carbon::yesterday()->format('Y-m-d');
            $kasbons = CashAdvance::where('status', 3)
                ->whereRaw("due_date <= '{$yesterday}'")->get();
        } else {
            $now = Carbon::now()->format('Y-m-d');
            $kasbons = CashAdvance::where('status', 3)
                ->whereRaw("due_date <= '{$now}'")->get();
        }

        if(empty($kasbons))
            return;

        $notifName = "Kasbon Kedaluwarsa";

        foreach($kasbons as $kasbon) {
            $receiver = User::find($kasbon->create_by);
            $notifDesc = "Kasbon {$kasbon->code} kedaluwarsa";
            self::notifyUsers($kasbon, auth()->user(), $notifName, $notifDesc, $receiver);

            $kasbon->update(['status' => 5]);
            CashAdvanceStatus::create([
                'header_id' => $kasbon->id,
                'status' => 5,
                'user_id' => auth()->id()
            ]);
        }

        DB::commit();

    }

    /**
     * Closing kasbon
     *
     * @return \Illuminate\Http\Response
     */
    public function close(Request $request, $id)
    {
        DB::beginTransaction();

        CashAdvance::find($id)->update([
            'status' => 6,
            'date_closed' => Carbon::now()->format('Y-m-d')
        ]);

        CashAdvanceStatus::create([
            'header_id' => $id,
            'status' => 6,
            'user_id' => auth()->id()
        ]);

        DB::commit();
    }

    /**
     * Tolak pengajuan approval awal kasbon
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'cancel_description' => 'required',
        ]);
        DB::beginTransaction();
        CashAdvance::find($id)->update([
            'status' => 7,
            'cancel_description' => $request->cancel_description
        ]);
        CashAdvanceStatus::create([
            'header_id' => $id,
            'status' => 7,
            'user_id' => auth()->id()
        ]);
        DB::commit();
    }

    public static function notifyUsers($cashAdvance, $user, $name, $description, $receiver=null)
    {
        // Notifikasi
        $slug=str_random(6);

        $n = Notification::create([
            'notification_type_id' => 15,
            'name' => $name,
            'description' => $description,
            'slug' => $slug,
            'route' => 'finance.kas_bon.show',
            'parameter' => json_encode(['id' => $cashAdvance->id])
        ]);

        if(is_null($receiver)) {
            $userList=DB::table('notification_type_users')
                ->leftJoin('users','users.id','=','notification_type_users.user_id')
                ->whereRaw("notification_type_users.notification_type_id = 15")
                ->select('users.id','users.is_admin','users.company_id')->get();

            foreach ($userList as $un) {
                if ($un->company_id == $user->company_id) {
                    NotificationUser::create([
                        'notification_id' => $n->id,
                        'user_id' => $un->id
                    ]);
                }
            }
        } else {
            if(is_array($receiver)){
                foreach($receiver as $r) {
                    NotificationUser::create([
                        'notification_id' => $n->id,
                        'user_id' => $r->id
                    ]);
                }
            } else {
                NotificationUser::create([
                    'notification_id' => $n->id,
                    'user_id' => $receiver->id
                ]);
            }
        }
    }
}
