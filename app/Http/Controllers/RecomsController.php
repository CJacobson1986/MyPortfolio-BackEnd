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

class RecomsController extends Controller
{
  public function __construct()
  {
    $this -> middleware('jwt.auth', ['except' => ['index', 'getInfo', 'main', 'getTopics', 'getNew', 'getFeatured', 'getChannels', 'getChannel', 'getDetail', 'getReplies', 'getUser', 'search' ]]);
  }

  public function index()
  {
      return File::get('index.html');
  }

  public function getNew(Request $request, $channel = 0, $count = 25)
  {
    if($channel == '0')
    {
      $topics = Ftopic::join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id') -> join('users', '=', 'users.id') -> orderBy('fchannels.id', 'fchannels.created_at', 'DESC') -> select('ftopics.id', 'ftopics.topicTitle', 'ftopics.topicSlug', 'ftopics.topicReplies', 'ftopics.topicViews', 'ftopics.topicChannel', 'fchannels.channelSlug', 'fchannels.channelTitle', 'users.name', 'users.avatar') -> paginate($count);
    }
    else
    {
      $channel = Fchannel::where('channelSlug', '=', $channel) -> select('id', 'channelTitle', 'channelDesc') -> first();
      $topics = Ftopic::where('ftopics.topicChannel', '=', $channel->id) -> join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id') -> join('users', '=', 'users.id') -> orderBy ('fchannels.id', 'fchannels.created_at', 'DESC') -> select('ftopics.id', 'ftopics.topicTitle', 'ftopics.topicSlug','ftopics.topicReplies', 'ftopics.topicViews', 'ftopics.topicChannel', 'fchannels.channelSlug', 'fchannels.channelTitle', 'users.name', 'users.avatar') -> paginate($count);
      foreach($topics as $key => $value)
      {
        $reply = Freply::where('freplies.topicID', '=', $value->id) -> join('users', 'freplies.userID', '=', 'users.id') -> orderBy('freplies.id', 'freplies.created_at', 'DESC') -> first();
        if(!empty($reply))
        {
          $value['name'] = $reply->name;
          $value['avatar'] = $reply->avatar;
        }
      }
    }
    return Response::json($topics);
  }

  public function getFeatured(Request $request)
  {
    $features = Ftopic::where('ftopics.topicFeature', '=', 1) -> join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id') -> join('users', '=', 'users.id') -> orderBy('fchannels.id', 'fchannels.created_at', 'DESC') -> select('ftopics.id', 'ftopics.topicTitle', 'ftopics.topicSlug', 'ftopics.topicReplies', 'ftopics.topicViews', 'ftopics.topicChannel',
    'fchannels.channelSlug', 'fchannels.channelTitle', 'users.name', 'users.avatar') -> get();
    foreach($features as $key => $value)
    {
      $reply = Freply::where('freplies.topicID', '=', $value -> id) -> join('users', 'freplies.userID', '=', 'users.id') -> orderBy('ftopics.id', 'ftopics.created_at', 'DESC') -> first();
      if(!empty($reply))
      {
        $value['name'] = $reply->name;
        $value['avatar'] = $reply->avatar;
      }
    }

    return Response::json($features);
  }

  public function getChannels(Request $request)
  {
    $channels = Fchannel::where('channelArchived', '=', 0) -> select('id', 'created_at', 'channelTitle', 'channelSlug', 'channelDesc') -> orderBy('id', 'created_at', 'DESC') -> get();

    return Response::json($channels);
  }

  public function getChannel(Request $request, $slug)
  {
    $channel = Fchannel::where('channelSlug', '=', $slug) -> select('id', 'created_at', 'channelTitle', 'channelSlug', 'channelDesc') -> first();

    return Response::json($channel);
  }

public function search(Request $request)
{
  $rules = array(
    'searchContent' => 'required'
  );
  $validator = Validator::make($request->all(), $rules);

  if ($validator->fails()) {
    return Response::json(['error'=> 'Please enter search content.']);
  } else {

    $searchContent = $request -> input('searchContent');

    $result = Ftopic::where('ftopics.topicTitle', '%'.$searchContent.'%') -> join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id') -> orderBy('ftopics.id', 'ftopics.created_at', 'DESC') -> select('ftopics.id', 'ftopics.userID', 'ftopics.topicTitle', 'ftopics.topicSlug', 'ftopics.topicReplies', 'ftopics.topicViews', 'ftopics.topicChannel', 'fchannels.channelTitle') -> paginate(25);

    return Response::json($result);
  }
}

public function storeChannel(Request $request)
{
  $user = Auth::user();
  if($user -> roleID == 1)
  {
    $rules = array(
      'channelTitle'	=> 	'required'
    );
    $validator = Validator::make($request -> all(), $rules);

    if ($validator -> fails())
    {
        return Response::json(['error'=> 'Please enter a channel title.']);
    }
    else
    {

      $channelTitle = $request -> input('channelTitle');
      $channelDesc = $request -> input('channelDesc');

      if (preg_match('/[A-Za-z]/', $channelTitle) || preg_match('/[0-9]/', $channelTitle))
      {
        $channelSlug = str_replace(' ', '-', $channelTitle);
        $channelSlug = preg_replace('/[^A-Za-z0-9\-]/', '', $channelSlug);
        $channelSlug = preg_replace('/-+/', '-', $channelSlug);

        if (Fchannel::where('channelSlug', '=', $channelSlug)->exists())
        {
           $channelSlug = $channelSlug.'_'.mt_rand(1, 9999);
        }
        if(empty($channelDesc))
        {
          $channelDesc = "No Description";
        }

        $channel = new Fchannel;
        $channel -> channelTitle = $channelTitle;
        $channel -> channelDesc = $channelDesc;
        $channel -> channelSlug = $channelSlug;
        $channel -> channelArchived=0;
        $channel -> save();

        $channelData = Fchannel::where('id', '=', $channel->id) -> select('id', 'channelTitle', 'channelDesc', 'channelSlug') -> first();
        return Response::json($channelData);
      }
      else
      {
        return Response::json(['error'=> 'Unable to create channel.']);
      }
    }
  }
  else
  {
    return Response::json(['error'=> 'Unauthorized user.']);
  }
}

public function updateChannel(Request $request, $id)
{
  $user = Auth::user();
  if($user -> roleID == 1)
  {
    $rules = array(
      'channelTitle' => 'required'
    );
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return Response::json(['error'=> 'Please enter a channel title.']);
    } else {

      $channel = Fchannel::find($id);

      $channelTitle = $request -> input('channelTitle');
      $channelDesc = $request -> input('channelDesc');

      if($channelTitle != NULL)
      {
        if (preg_match('/[A-Za-z]/', $channelTitle) || preg_match('/[0-9]/', $channelTitle)) {
          $channelSlug = str_replace(' ', '-', $channelTitle);
          $channelSlug = preg_replace('/[^A-Za-z0-9\-]/', '', $channelSlug);
          $channelSlug = preg_replace('/-+/', '-', $channelSlug);

          if (Fchannel::where('channelSlug', '=', $channelSlug)->exists()) {
             $channelSlug = $channelSlug.'_'.mt_rand(1, 9999);
          }

          $channel -> channelTitle = $channelTitle;
          $channel -> channelSlug = $channelSlug;
        } else {
          return Response::json(['error'=> 'Channel does not exist.']);
        }
      }

      if($channelDesc != NULL)
      {
        $channel -> channelDesc = $channelDesc;
      }
      else {
        $channel -> channelDesc = "No Description.";
      }

      $channel -> save();

      $channelData = Fchannel::where('id', '=', $channel -> id) -> select('id', 'channelTitle', 'channelDesc', 'channelSlug') -> first();
      return Response::json($channelData);
    }
  } else {
    return Response::json(['error'=> 'Unauthorized user.']);
  }
}

public function deleteChannel(Request $request, $id)
{
  $user = Auth::user();
  if($user -> roleID == 1)
  {
    $channel = Fchannel::find($id);

    if($channel -> id != 1)
    {
      $topics = Ftopic::where('topicChannel', '=', $channel -> id) -> get();
      if(!$topics -> isEmpty())
      {
        foreach($topics as $key => $value)
        {
          $value -> topicChannel = 1;
          $value -> save();
        }
      }
      $channel -> delete();

      return Response::json(['success'=> 'Channel deleted.']);
    }
    else {
      return Response::json(['error'=> 'Unable to delete channel.']);
    }
  } else {
    return Response::json(['error'=> 'Unauthorized user.']);
  }
}

}
