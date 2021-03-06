<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Auth;
use App\Ftopic;
use App\Freply;
use App\User;
use App\Fchannel;
use App\Option;
use \DB;
use \Response;
use \Input;
use \Image;
use \File;
use \Mail;
use \DateTime;
use \Purifier;
use GrahamCampbell\Markdown\Facades\Markdown;
use League\HTMLToMarkdown\HtmlConverter;

class CommentController extends Controller
{
  public function __construct()
  {
    $this->middleware('jwt.auth', ['only' => ['storeReply', 'updateReply', 'deleteReply']]);
  }
  public function getReplies(Request $request, $slug)
  {
    $topic = Ftopic::where('ftopics.topicSlug', '=', $slug) -> select('ftopics.id') -> first();

    $replies = Freply::where('freplies.topicID', '=', $topic -> id) -> join('users', 'freplies.userID', '=', 'users.id') -> orderBy('freplies.userID', 'freplies.created_at', 'DESC') -> select('freplies.id', 'freplies.userID', 'users.avatar', 'users.name', 'freplies.replyBody') -> paginate(25);


    return Response::json(['replies' => $replies]);
  }

  public function storeReply(Request $request)
  {
    $user = Auth::user();
    $validator = Validator::make($request -> all(),
    [
      'topicID'		=> 	'required',
      'replyBody'	=>	'required'
    ]);

    if ($validator -> fails())
    {
        return Response::json(['message'=> 'Please fill out all fields']);
    }
    else {
      $topicID = $request -> input('topicID');
      $replyBody = $request -> input('replyBody');

      $topicCheck = Ftopic::find($topicID);

      if($topicCheck -> allowReplies == 0)
      {
        return Response::json(['message'=> 'Did not find the topic.']);
      }

      if(strlen($replyBody) > 500)
      {
        return Response::json(['message'=> 'Please limit your responses to less than 500 characters.']);
      }
      if(substr_count($replyBody, 'img') > 1 || substr_count($replyBody, 'href') > 1 || substr_count($replyBody, 'youtube.com') > 1)
      {
        return Response::json(['message'=> 'Please dont spam, too many links.']);
      }
      else
      {

        $reply = new Freply;
        $reply->topicID = $topicID;
        $reply->replyBody = $replyBody;
        $reply->userID = $user->id;
        $reply->save();

        $topic = Ftopic::where('id', '=', $topicID) -> first();
        $topic->increment('topicReplies');

        $replyData = Freply::where('freplies.id', '=', $reply -> id) -> join('users', 'freplies.userID', '=', 'users.id') -> select('freplies.id', 'freplies.created_at', 'freplies.replyBody', 'users.avatar', 'users.name') -> first();
        return Response::json(['replyData' => $replyData, 'message' => 'Reply Successfully Created']);
      }
    }
  }


  public function updateReply(Request $request, $id)
  {

    $user = Auth::user();
    $reply = Freply::find($id);

    if($user -> roleID == 1 || $user -> id == $reply -> userID)
    {
      $rules = array(
        'replyBody'			=>	'required'
      );
      $validator = Validator::make($request->all(), $rules);

      if ($validator->fails()) {
          return Response::json(['error'=> 'Please enter a reply.']);
      } else {

        $replyBody = $request -> input('replyBody');
        $userID = Auth::user();
        $topicID = $reply -> topicID;

        $topicCheck = Ftopic::find($topicID);
        if($topicCheck -> allowReplies == 0)
        {
          return Response::json(['success'=> 'Thank you for your reply.']);
        }

        $pastReplies = Freply::where('userID', '=', $userID->id) -> select('id', 'created_at') -> orderBy('id', 'created_at', 'DESC') -> skip(5) -> take(1) -> first();
        $currentTime = date('Y-m-d H:i:s');

        if(!empty($pastReplies) && $userID -> roleID != 1)
        {
          $datetime1 = new DateTime($pastReplies -> created_at);
          $datetime2 = new DateTime($currentTime);
          $interval = $datetime1 -> diff($datetime2);

          if($interval -> format('%a%H') < 1) {
            return Response::json(['Sorry'=> 'Please wait longer to make another reply.']);
          }
        }

        if(strlen($replyBody) > 500)
        {
          return Response::json(['Sorry'=> 'Please limit your responses to less than 500 characters.']);
        }
        else {

          $replyBody = Markdown::convertToHtml($replyBody);
          $replyBody = Purifier::clean($replyBody);
          $converter = new HtmlConverter();
          $replyBody = $converter -> convert($replyBody);

          if(substr_count($replyBody, 'img') > 1 || substr_count($replyBody, 'href') > 1 || substr_count($replyBody, 'youtube.com') > 1)
          {
            return Response::json(['success'=> 'Thank you for your reply.']);
          }
          else {

            $reply -> replyBody = $replyBody;
            $reply -> save();

            $replyData = Freply::where('freplies.id', '=', $reply->id) -> join('users', 'freplies.userID', '=', 'users.id') -> select('freplies.id',  'freplies.created_at', 'freplies.replyBody', 'users.avatar', 'users.name') -> first();
            return Response::json($replyData);
          }
        }
      }
    } else {
      return Response::json(['error'=> 'UnexpectedError.']);
    }
  }

  public function deleteReply(Request $request)
  {
    $user = Auth::user();
    $id = $request -> input('replyID');
    $reply = Freply::find($id);
    if($user -> roleID == 1 || $user -> id == $reply -> userID)
    {
      $user = User::where('id', '=', $reply -> userID) -> first();
      $topic = Ftopic::find($reply -> replyID);

      if($user -> replies > 0)
      {
        $user -> replies = $user -> replies - 1;
        $user -> save();
      }

      if($topic -> topicReplies > 0)
      {
        $topic -> topicReplies = $topic -> topicReplies - 1;
        $topic -> save();
      }

      $reply -> delete();

      return Response::json(['success'=> 'Your reply has been removed.']);
    } else {
      return Response::json(['error'=> 'Unable to remove.']);
    }
  }

}
