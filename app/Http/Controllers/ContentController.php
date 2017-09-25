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

class ContentController extends Controller
{
  public function __construct()
  {
    $this->middleware('jwt.auth', ['only' => ['storeTopic', 'updateTopic', 'setFeature', 'deleteTopic']]);
  }

  public function getInfo(Request $request)
  {
    $info = Option::select('website') -> first();

    return Response::json($info);
  }

  /*public function getFeatured(Request $request)
  {
    $features = Ftopic::where('ftopics.topicFeature', '=', 1) ->
    join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id') ->
    join('users', 'ftopics.userID', '=', 'users.id') ->
    orderBy('ftopics.id', 'DESC') ->
    select('ftopics.id', 'ftopics.topicTitle', 'ftopics.topicSlug', 'ftopics.topicReplies', 'ftopics.topicViews', 'ftopics.topicChannel',
    'fchannels.channelSlug', 'fchannels.channelTitle', 'users.name', 'users.avatar') -> get();

    return Response::json($features);
  }*/

  public function main()
  {
    $options = Option::select('website', 'baseurl', 'siteLogo', 'aboutWebsite') -> first();

    return Response::json(['options' => $options]);
  }

  public function getTopics($channel = 0, $count = 25)
  {
    if($channel == '0')
    {
      $topics = Ftopic::join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id') ->
      join('users', 'ftopics.userID', '=', 'users.id') ->
      orderBy('ftopics.updated_at', 'DESC') ->
      select('ftopics.id', 'ftopics.userID', 'ftopics.topicTitle', 'ftopics.topicSlug', 'ftopics.topicChannel', 'fchannels.channelSlug', 'fchannels.channelTitle', 'users.name', 'users.avatar') -> paginate($count);
    }
    else
    {
      $channel = Fchannel::where('channelSlug', '=', $channel) ->
      select('id', 'channelTitle', 'channelDesc') -> first();

      $topics = Ftopic::where('ftopics.topicChannel', '=', $channel -> id) ->
       join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id') ->
       join('users', 'ftopics.userID', '=', 'users.id') ->
       orderBy('ftopics.userID', 'ftopics.created_at', 'DESC') ->
       select('ftopics.id', 'ftopics.userID', 'ftopics.topicTitle', 'ftopics.topicSlug', 'ftopics.topicViews', 'ftopics.topicChannel', 'fchannels.channelSlug', 'fchannels.channelTitle', 'users.name', 'users.avatar') -> paginate($count);
    }
    return Response::json($topics);
  }

  public function getDetail($slug)
  {
    $topic = Ftopic::where('ftopics.topicSlug', '=', $slug) ->
    join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id') ->
    select('ftopics.id', 'ftopics.userID', 'ftopics.topicTitle', 'ftopics.topicChannel', 'ftopics.topicReplies', 'ftopics.topicViews', 'ftopics.topicBody', 'fchannels.channelTitle', 'ftopics.allowReplies', 'ftopics.topicSlug') -> first();

    $user = User::where('id', '=', $topic -> userID) ->
    select('id', 'name', 'avatar') -> first();

    $topic -> timestamps = false;
    $topic -> increment('topicViews');

    return Response::json(['topic' => $topic, 'user' => $user ]);
  }

  public function createTopic()
  {
    $user = Auth::user();
    $channels = Fchannel::where('channelArchived', '=', 0) ->
    select('id', 'channelTitle') -> get();

    return Response::json(['channels' => $channels]);
  }

  public function storeTopic(Request $request)
  {
    $user = Auth::user();
    $validator = Validator::make($request -> all(),
    [
      'topicTitle'  =>  'required',
      'topicChannel' => 'required',
      'topicBody' => 'required'
    ]);

    if ($validator->fails())
    {
      return Response::json(['message' => 'Please fill out all topic fields.']);
    }
    else
    {

      $topicTitle = $request -> input('topicTitle');
      $topicBody = $request -> input('topicBody');
      $topicChannel = $request -> input('topicChannel');
      $allowReplies = 1;

      if (preg_match('/[A-Za-z]/', $topicTitle) || preg_match('/[0-9]/', $topicTitle))
      {
        $topicSlug = str_replace(' ', '-', $topicTitle);
        $topicSlug = preg_replace('/[^A-Za-z0-9\-]/', '', $topicSlug);
        $topicSlug = preg_replace('/-+/', '-', $topicSlug);
      }

      if(strlen($topicSlug > 15))
      {
        $topicSlug = substr($topicSlug, 0, 15);
      }

      if (Ftopic::where('topicSlug', '=', $topicSlug) -> exists())
      {
         $topicSlug = $topicSlug.'_'.mt_rand(1, 9999);
      }

      /*$pastTopics = Ftopic::where('userID', '=', $user -> id) -> select('userID', 'created_at') -> orderBy('userID', 'DESC') -> skip(5) -> take(1) -> first();
      $currentTime = date('Y-m-d H:i:s');

      if(!empty($pastTopics) && $user -> roleID != 1)
      {
        $datetime1 = new DateTime($pastTopics -> created_at);
        $datetime2 = new DateTime($currentTime);
        $interval = $datetime1 -> diff($datetime2);

        if($interval -> format('%a%H') < 1)
          {
            return Response::json(['sorry'=> 'Please wait to enter another topic.']);
          }
      }*/

      if(strlen($topicBody) > 1500)
      {
        return Response::json(['message'=> 'Please limit your entry to 1500 characters.']);
      }
      else
      {

        //$topicBody = Markdown::convertToHtml($topicBody);
        //$topicBody = ($topicBody);

        //$converter = new HtmlConverter();
        //$topicBody = $converter -> convert($topicBody);

        if(substr_count($topicBody, 'img') > 5 || substr_count($topicBody, 'href') > 5 || substr_count($topicBody, 'youtube.com') > 2)
        {
          return Response::json(['message'=> 'Please dont spam, too many links.']);
        }
        else
        {
          $topic = new Ftopic;
          $topic -> topicTitle = $topicTitle;
          $topic -> userID = $user -> id;
          $topic -> topicBody = $topicBody;
          $topic -> topicChannel = $topicChannel;
          $topic -> topicSlug = $topicSlug;
          $topic -> topicViews = 0;
          $topic -> topicReplies = 0;
          $topic -> topicArchived = 0;
          $topic -> allowReplies = $allowReplies;
          $topic -> save();

          $topicData = Ftopic::where('ftopics.id', '=', $topic->id) ->
          join('users', 'ftopics.userID', '=', 'users.id') ->
          join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id') ->
          select('ftopics.id', 'ftopics.userID', 'ftopics.topicTitle', 'ftopics.topicBody', 'ftopics.topicChannel', 'ftopics.topicSlug', 'ftopics.topicViews', 'ftopics.topicReplies',  'ftopics.allowReplies', 'users.id', 'users.avatar', 'users.name', 'fchannels.channelTitle') -> first();
          return Response::json(['topicData' => $topicData, 'message' =>  'New Topic Created']);
        }
      }
    }
  }

  public function updateTopic(Request $request, $id)
  {
    $user = Auth::user();
    $topic = Ftopic::find($id);

    if($user -> roleID == 1 || $user -> id == $topic -> userID)
    {
      $rules = array(
        'topicTitle'		=> 	'required',
        'topicChannel' => 'required',
        'topicBody' => 'required'
      );
      $validator = Validator::make($request -> all(), $rules);

      if ($validator -> fails()) {
          return Response::json(['error'=> 'Please fill out all topic fields.']);
      } else {

        $topicTitle = $request -> input('topicTitle');
        $topicBody = $request -> input('topicBody');
        $topicChannel = $request -> input('topicChannel');
        $allowReplies = 1;

        if($topicTitle != NULL)
        {
          if (preg_match('/[A-Za-z]/', $topicTitle) || preg_match('/[0-9]/', $topicTitle)) {
            $topicSlug = str_replace(' ', '-', $topicTitle);
            $topicSlug = preg_replace('/[^A-Za-z0-9\-]/', '', $topicSlug);
            $topicSlug = preg_replace('/-+/', '-', $topicSlug);

            if (Ftopic::where('topicSlug', '=', $topicSlug) -> where('id', '!=', $topic->id) -> exists()) {
               $topicSlug = $topicSlug.'_'.mt_rand(1, 9999);
            }

            $topic->topicTitle = $topicTitle;
            $topic->topicSlug = $topicSlug;
          } else {
            return Response::json(['error'=> 'Topic does not exist.']);
          }
        }
        if($topicBody != NULL)
        {
          $pastTopics = Ftopic::where('userID', '=', $user -> id) -> select('id', 'created_at') -> orderBy('id', 'created_at', 'DESC') -> skip(5) -> take(1) -> first();
          $currentTime = date('Y-m-d H:i:s');

          if(!empty($pastTopics) && $user -> roleID != 1)
          {
            $datetime1 = new DateTime($pastTopics -> created_at);
            $datetime2 = new DateTime($currentTime);
            $interval = $datetime1 -> diff($datetime2);

            if($interval->format('%a%H') < 1) {
              return Response::json(['sorry'=> 'Please wait to make another update.']);
            }
          }

          if(strlen($topicBody) > 1500)
          {
            return Response::json(['error'=> 'Please limit your entry to 1500 characters.']);
          }
          else {

            /*$topicBody = Markdown::convertToHtml($topicBody);
            $topicBody = $topicBody;

            $converter = new HtmlConverter();
            $topicBody = $converter -> convert($topicBody);
            */

            if(substr_count($topicBody, 'img') > 5 || substr_count($topicBody, 'href') > 5 || substr_count($topicBody, 'youtube.com') > 2)
            {
              return Response::json(['error'=> 'Too many links, please do not spam.']);
            }
            else {
              $topic -> topicBody = $topicBody;
            }
          }
        }
        if($topicChannel != NULL)
        {

          $topic -> topicChannel = $topicChannel;
        }

        $topic -> allowReplies = $allowReplies;
        $topic -> save();

        $topicData = Ftopic::where('ftopics.id', '=', $topic -> id) ->
        join('users', 'ftopics.userID', '=', 'users.id') ->
        join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id') ->
        select('ftopics.id', 'ftopics.userID', 'ftopics.topicTitle', 'ftopics.topicBody', 'ftopics.topicChannel', 'ftopics.topicSlug', 'ftopics.topicViews', 'ftopics.topicReplies', 'ftopics.topicFeature', 'ftopics.allowReplies', 'users.id', 'users.avatar', 'users.name', 'fchannels.channelTitle') -> first();
        return Response::json($topicData);
      }
    } else {
      return Response::json(['error'=> 'Unauthorized user.']);
    }
  }

  public function deleteTopic(Request $request, $id)
  {
    $user = Auth::user();
    $topic = Ftopic::find($id);

    if($user -> roleID == 1 || $user -> id == $topic -> userID)
    {
      $replies = Freply::where('topicID', '=', $topic -> id) -> get();
      $user = User::where('id', '=', $topic -> userID) -> first();
      $channel = Fchannel::where('id', '=', $topic -> topicChannel) -> first();

      if($user -> topics > 0)
      {
        $user -> topics = $user -> topics - 1;
        $user -> save();
      }

      if($channel -> channelTopics > 0)
      {
        $channel -> channelTopics = $channel -> channelTopics - 1;
        $channel -> save();
      }

      if(!empty($replies))
      {
        foreach($replies as $key => $value)
        {

          $user = User::where('id', '=', $value -> userID) -> first();
          if($user -> replies > 0)
          {
            $user -> replies = $user -> replies - 1;
            $user -> save();
          }


          $value -> delete();
        }
      }
      $topic -> delete();

      return Response::json(['success'=> 'Topic removed.']);
    } else {
      return Response::json(['error'=> 'Unable to remove item.']);
    }
  }

  /*public function setFeature(Request $request, $id)
  {
    $user = Auth::user();
    if($user -> roleID == 1)
    {
      $topic = Ftopic::find($id);
      if($topic -> topicFeature == 0)
      {
        $topic -> topicFeature = 1;
        $topic -> save();
        //Featured
        return Response::json(['success'=> 'Added topic to the featured list.']);
      }
      else if($topic -> topicFeature == 1)
      {
        $topic -> topicFeature = 0;
        $topic -> save();
        //Unfeatured
        return Response::json(['success'=> 'Removed topic from featured list.']);
      }
    } else {
      return Response::json(['error'=> 'Unauthorized user.']);
    }
  }*/


}
