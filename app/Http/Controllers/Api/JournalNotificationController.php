<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Journal;
use DB;
use Response;

class JournalNotificationController extends Controller
{
    public function get_notif()
    {
        $user_id = auth()->id();
        $data = DB::table('journals')
            ->where('status', 2)
            ->whereRaw("journals.id NOT IN (SELECT journal_id FROM noticed_journal_notifications WHERE user_id = $user_id)")
            ->orderBy('date_transaction','desc')
            ->get();

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function detail_notification()
    {
        $data['notification'] = DB::table('journals')
            ->where('status', 2)
            ->orderBy('date_transaction','asc')
            ->get();
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function viewNotif($journal_id)
    {
        DB::beginTransaction();
        $latestJournal = DB::table('noticed_journal_notifications')
        ->whereJournalId($journal_id)
        ->whereUserId(auth()->id())
        ->count('id');

        if($latestJournal == 0) {
            DB::table('noticed_journal_notifications')
            ->insert([
                'journal_id' => $journal_id,
                'user_id' => auth()->id(),
            ]);
        }
        DB::commit();
        
        return Response::json(['message' => 'Notifikasi telah dibaca'], 200, [], JSON_NUMERIC_CHECK);
    }
}
