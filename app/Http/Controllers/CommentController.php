<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CommentController extends Controller
{
  public function getReplies(Request $request, $slug)
  {
    $topic = Ftopic::where('ftopics.topicSlug', '=', $slug)->select('ftopics.id')->first();

    $replies = Freply::where('freplies.topicID', '=', $topic->id)->join('users', 'freplies.replyAuthor', '=', 'users.id')->orderBy('freplies.created_at', 'ASC')->select('freplies.id', 'freplies.created_at', 'freplies.replyBody', 'freplies.replyAuthor', 'users.avatar', 'users.name', 'users.displayName')->paginate(25)->toArray();


    return Response::json(['replies' => $replies]);
  }

  public function storeReply(Request $request)
  {
    $rules = array(
      'topicID'		=> 	'required',
      'replyBody'			=>	'required'
    );
    $validator = Validator::make($request->json()->all(), $rules);

    if ($validator->fails()) {
        return Response::json(['error'=> 'Please fill out all fields']);
    } else {

      $topicID = $request->input('topicID');
      $replyBody = $request->input('replyBody');
      $replyAuthor = Auth::user();

      $topicCheck = Ftopic::find($topicID);
      if($topicCheck->allowReplies == 0)
      {
        return Response::json(['Sorry'=> 'Did not find the topic.']);
      }

      $pastReplies = Freply::where('replyAuthor', '=', $replyAuthor->id)->select('id', 'created_at')->orderBy('id', 'DESC')->skip(5)->take(1)->first();
      $currentTime = date('Y-m-d H:i:s');

      if(!empty($pastReplies) && $replyAuthor->roleID != 1)
      {
        $datetime1 = new DateTime($pastReplies->created_at);
        $datetime2 = new DateTime($currentTime);
        $interval = $datetime1->diff($datetime2);

        if($interval->format('%a%H') < 1) {
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
        $replyBody = $converter->convert($replyBody);

        if(substr_count($replyBody, 'img') > 1 || substr_count($replyBody, 'href') > 1 || substr_count($replyBody, 'youtube.com') > 1)
        {
          return Response::json(['error'=> 'Please dont spam, too many links.']);
        }
        else {


          $reply = new Freply;
          $reply->topicID = $topicID;
          $reply->replyBody = $replyBody;
          $reply->replyAuthor = $replyAuthor->id;

          $reply->save();

          $replyAuthor->increment('replies');
          $topic = Ftopic::where('id', '=', $topicID)->first();
          $topic->increment('topicReplies');

          $replyData = Freply::where('freplies.id', '=', $reply->id)->join('users', 'freplies.replyAuthor', '=', 'users.id')->select('freplies.id', 'freplies.created_at', 'freplies.replyBody', 'users.avatar', 'users.name', 'users.displayName')->first();
          $replyData['childReplies'] = array();
          return Response::json($replyData);
        }
      }
    }
  }

  public function updateReply(Request $request, $id)
  {

    $user = Auth::user();
    $reply = Freply::find($id);

    if($user->roleID == 1 || $user->id == $reply->replyAuthors)
    {
      $rules = array(
        'replyBody'			=>	'required'
      );
      $validator = Validator::make($request->json()->all(), $rules);

      if ($validator->fails()) {
          return Response::json(['error'=> 'Please enter a reply.']);
      } else {

        $replyBody = $request->input('replyBody');
        $replyAuthor = Auth::user();
        $topicID = $reply->topicID;

        $topicCheck = Ftopic::find($topicID);
        if($topicCheck->allowReplies == 0)
        {
          return Response::json(['success'=> 'Thank you for your reply.']);
        }

        $pastReplies = Freply::where('replyAuthor', '=', $replyAuthor->id)->select('id', 'created_at')->orderBy('id', 'DESC')->skip(5)->take(1)->first();
        $currentTime = date('Y-m-d H:i:s');

        if(!empty($pastReplies) && $replyAuthor->role != 1)
        {
          $datetime1 = new DateTime($pastReplies->created_at);
          $datetime2 = new DateTime($currentTime);
          $interval = $datetime1->diff($datetime2);

          if($interval->format('%a%H') < 1) {
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
          $replyBody = $converter->convert($replyBody);

          if(substr_count($replyBody, 'img') > 1 || substr_count($replyBody, 'href') > 1 || substr_count($replyBody, 'youtube.com') > 1)
          {
            return Response::json(['success'=> 'Thank you for your reply.']);
          }
          else {

            $reply->replyBody = $replyBody;
            $reply->save();

            $replyData = Freply::where('freplies.id', '=', $reply->id)->join('users', 'freplies.replyAuthor', '=', 'users.id')->select('freplies.id',  'freplies.created_at', 'freplies.replyBody', 'users.avatar', 'users.name', 'users.displayName')->first();
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
    $id = $request->input('replyID');
    $reply = Freply::find($id);
    if($user->roleID == 1 || $user->id == $reply->replyAuthor)
    {
      $user = User::where('id', '=', $reply->replyAuthor)->first();
      $topic = Ftopic::find($reply->topicID);

      if($user->replies > 0)
      {
        $user->replies = $user->replies - 1;
        $user->save();
      }

      if($topic->topicReplies > 0)
      {
        $topic->topicReplies = $topic->topicReplies - 1;
        $topic->save();
      }

      $reply->delete();

      return Response::json(['success'=> 'Your reply has been removed.']);
    } else {
      return Response::json(['error'=> 'Unable to remove.']);
    }
  }


}
