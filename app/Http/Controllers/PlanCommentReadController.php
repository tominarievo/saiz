<?php

namespace App\Http\Controllers;

use App\PlanComment;
use App\PlanCommentRead;
use Illuminate\Http\Request;

/*
 * 予定コメントの既読状態に関する操作を処理するコントローラー
 */
class PlanCommentReadController extends Controller
{
   /*
    * 予定コメントの既読状態を更新する
    *
    * @param  \Illuminate\Http\Request  $request  リクエスト
    */
    public function update(Request $request)
    {
        $comment_id  = $request->input('comment_id');
        $read_status = $request->input('read_status');

        $comment = PlanComment::findOrFail($comment_id);

        $read = PlanCommentRead::where("plan_comment_id", $comment_id)
            ->where('user_id', \Auth::user()->id)
            ->first();

        if ( ! $read) {
            $read = new PlanCommentRead();
            $read->plan_comment_id = $comment->id;
            $read->plan_id         = $comment->plan_id;
            $read->user_id         = \Auth::user()->id;
        }

        $read->read_status     = $read_status;
        $read->save();
    }
}
