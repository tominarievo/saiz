<?php

namespace App\Http\Controllers;

use App\Plan;
use App\PlanComment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/*
 * 予定コメントに関する操作を処理するコントローラー
 */
class PlanCommentController extends Controller
{
    /**
     * 予定コメントを保存する
     *
     * @param  \Illuminate\Http\Request  $request  リクエスト
     * @return \Illuminate\Http\Response  レスポンス
     */
    public function store(Request $request)
    {
		$plan_comment = new PlanComment();
        $plan_comment->plan_id = $request->input('plan_id');
        $plan_comment->user_id = Auth::user()->id;
        $plan_comment->post_datetime = Carbon::now();
        $plan_comment->comment = $request->input('comment');
        $plan_comment->save();

        $plan = Plan::findOrFail($plan_comment->plan_id);

        $ret = new \stdClass();
        $ret->planComments = $plan->planComments;

		return $ret;
    }

    /**
     * 予定コメントを削除する
     *
     * @param  \Illuminate\Http\Request  $request  リクエスト
     * @return \Illuminate\Http\Response  レスポンス
     */
    public function delete(Request $request)
    {
        $comment = PlanComment::findOrFail($request->input("commentId"));
        $comment->delete();

        $plan = Plan::findOrFail($request->input('plan_id'));

        $ret = new \stdClass();
        $ret->planComments = $plan->planComments;

        return $ret;
    }
}
