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

class MainController extends Controller
{
  public function __construct()
  {
    $this->middleware('jwt.auth', ['except' => ['index', 'getInfo', 'main', 'getTopics', 'getNew', 'getFeatured', 'getChannels', 'getChannel', 'getDetail', 'getReplies', 'getUser', 'search' ]]);
  }

  public function index()
  {
      return File::get('index.html');
  }

  public function getInfo(Request $request)
  {
    $info = Option::select('website')->first();

    return Response::json($info)->setCallback($request->input('callback'));
  }

  public function main(Request $request)
  {
    $options = Option::select('website', 'baseurl', 'siteLogo', 'aboutWebsite')->first();

    return Response::json(['options' => $options])->setCallback($request->input('callback'));
  }

  public function getTopics(Request $request, $channel = 0, $count = 25)
  {
    if($channel == '0')
    {
      $topics = Ftopic::where('ftopics.topicFeature', '=', 0)->join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id')->join('users', 'ftopics.topicAuthor', '=', 'users.id')->orderBy('ftopics.updated_at', 'DESC')->select('ftopics.id', 'ftopics.topicTitle', 'ftopics.topicSlug', 'ftopics.created_at', 'ftopics.updated_at', 'ftopics.topicReplies', 'ftopics.topicViews', 'ftopics.topicChannel', 'fchannels.channelSlug', 'fchannels.channelTitle', 'users.displayName', 'users.name', 'users.avatar')->paginate($count);
      foreach($topics as $key => $value)
      {
        $reply = Freply::where('freplies.topicID', '=', $value->id)->join('users', 'freplies.replyAuthor', '=', 'users.id')->orderBy('freplies.created_at', 'desc')->first();
        if(!empty($reply))
        {
          $value['displayName'] = $reply->displayName;
          $value['name'] = $reply->name;
          $value['avatar'] = $reply->avatar;
        }
      }
    }
    else
    {
      $channel = Fchannel::where('channelSlug', '=', $channel)->select('id', 'channelTitle', 'channelDesc', 'channelTopics')->first();
      $topics = Ftopic::where('ftopics.topicFeature', '=', 0)->where('ftopics.topicChannel', '=', $channel->id)->join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id')->join('users', 'ftopics.topicAuthor', '=', 'users.id')->orderBy('ftopics.updated_at', 'DESC')->select('ftopics.id', 'ftopics.topicTitle', 'ftopics.topicSlug', 'ftopics.created_at', 'ftopics.updated_at', 'ftopics.topicReplies', 'ftopics.topicViews', 'ftopics.topicChannel', 'fchannels.channelSlug', 'fchannels.channelTitle', 'users.displayName', 'users.name', 'users.avatar')->paginate($count);
      foreach($topics as $key => $value)
      {
        $reply = Freply::where('freplies.topicID', '=', $value->id)->join('users', 'freplies.replyAuthor', '=', 'users.id')->orderBy('freplies.created_at', 'desc')->first();
        if(!empty($reply))
        {
          $value['displayName'] = $reply->displayName;
          $value['name'] = $reply->name;
          $value['avatar'] = $reply->avatar;
        }
      }
    }
    return Response::json($topics)->setCallback($request->input('callback'));
  }

  public function getNew(Request $request, $channel = 0, $count = 25)
  {
    if($channel == '0')
    {
      $topics = Ftopic::join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id')->join('users', 'ftopics.topicAuthor', '=', 'users.id')->orderBy('ftopics.created_at', 'DESC')->select('ftopics.id', 'ftopics.topicTitle', 'ftopics.topicSlug', 'ftopics.created_at', 'ftopics.updated_at', 'ftopics.topicReplies', 'ftopics.topicViews', 'ftopics.topicChannel', 'fchannels.channelSlug', 'fchannels.channelTitle', 'users.displayName', 'users.name', 'users.avatar')->paginate($count);
    }
    else
    {
      $channel = Fchannel::where('channelSlug', '=', $channel)->select('id', 'channelTitle', 'channelDesc', 'channelTopics')->first();
      $topics = Ftopic::where('ftopics.topicChannel', '=', $channel->id)->join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id')->join('users', 'ftopics.topicAuthor', '=', 'users.id')->orderBy('ftopics.created_at', 'DESC')->select('ftopics.id', 'ftopics.topicTitle', 'ftopics.topicSlug', 'ftopics.updated_at', 'ftopics.created_at', 'ftopics.topicReplies', 'ftopics.topicViews', 'ftopics.topicChannel', 'fchannels.channelSlug', 'fchannels.channelTitle', 'users.displayName', 'users.name', 'users.avatar')->paginate($count);
      foreach($topics as $key => $value)
      {
        $reply = Freply::where('freplies.topicID', '=', $value->id)->join('users', 'freplies.replyAuthor', '=', 'users.id')->orderBy('freplies.created_at', 'desc')->first();
        if(!empty($reply))
        {
          $value['displayName'] = $reply->displayName;
          $value['name'] = $reply->name;
          $value['avatar'] = $reply->avatar;
        }
      }
    }
    return Response::json($topics)->setCallback($request->input('callback'));
  }

  public function getFeatured(Request $request)
  {
    $features = Ftopic::where('ftopics.topicFeature', '=', 1)->join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id')->join('users', 'ftopics.topicAuthor', '=', 'users.id')->orderBy('ftopics.updated_at', 'DESC')->select('ftopics.id', 'ftopics.topicTitle', 'ftopics.topicSlug', 'ftopics.created_at', 'ftopics.updated_at', 'ftopics.topicReplies', 'ftopics.topicViews', 'ftopics.topicChannel', 'fchannels.channelSlug', 'fchannels.channelTitle', 'users.displayName', 'users.name', 'users.avatar')->get();
    foreach($features as $key => $value)
    {
      $reply = Freply::where('freplies.topicID', '=', $value->id)->join('users', 'freplies.replyAuthor', '=', 'users.id')->orderBy('freplies.created_at', 'desc')->first();
      if(!empty($reply))
      {
        $value['displayName'] = $reply->displayName;
        $value['name'] = $reply->name;
        $value['avatar'] = $reply->avatar;
      }
    }

    return Response::json($features)->setCallback($request->input('callback'));
  }

  public function getChannels(Request $request)
  {
    $channels = Fchannel::where('channelArchived', '=', 0)->select('id', 'channelTitle', 'channelSlug', 'channelDesc', 'channelImg', 'channelTopics')->orderBy('created_at', 'DESC')->get();

    return Response::json($channels)->setCallback($request->input('callback'));
  }

  public function getChannel(Request $request, $slug)
  {
    $channel = Fchannel::where('channelSlug', '=', $slug)->select('id', 'channelTitle', 'channelSlug', 'channelDesc', 'channelTopics')->first();

    return Response::json($channel)->setCallback($request->input('callback'));
  }

  public function getDetail(Request $request, $slug)
  {
    $topic = Ftopic::where('ftopics.topicSlug', '=', $slug)->join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id')->select('ftopics.id', 'ftopics.topicTitle', 'ftopics.topicChannel', 'ftopics.created_at', 'ftopics.topicReplies', 'ftopics.topicViews', 'ftopics.topicBody', 'ftopics.topicAuthor', 'fchannels.channelTitle', 'ftopics.allowReplies')->first();
    $user = User::where('id', '=', $topic->topicAuthor)->select('id', 'name', 'displayName', 'avatar')->first();

    $topic->timestamps = false;
    $topic->increment('topicViews');

    $previousTopic = Ftopic::where('ftopics.id', '<', $topic->id)->where('topicChannel', '=', $topic->topicChannel)->select('ftopics.id', 'ftopics.topicTitle', 'ftopics.topicSlug', 'ftopics.topicChannel', 'ftopics.created_at')->orderBy('ftopics.id','desc')->first();
    $nextTopic = Ftopic::where('ftopics.id', '>', $topic->id)->where('topicChannel', '=', $topic->topicChannel)->select('ftopics.id', 'ftopics.topicTitle', 'ftopics.topicSlug', 'ftopics.topicChannel', 'ftopics.created_at')->orderBy('ftopics.id','asc')->first();

    return Response::json(['topic' => $topic, 'user' => $user, 'previousTopic' => $previousTopic, 'nextTopic' => $nextTopic])->setCallback($request->input('callback'));
  }

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

      $topicID = $request->json('topicID');
      $replyBody = $request->json('replyBody');
      $replyAuthor = Auth::user();

      $topicCheck = Ftopic::find($topicID);
      if($topicCheck->allowReplies == 0)
      {
        return Response::json(['Sorry'=> 'Did not find the topic.']);
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

    if($user->role == 1 || $user->id == $reply->replyAuthors)
    {
      $rules = array(
        'replyBody'			=>	'required'
      );
      $validator = Validator::make($request->json()->all(), $rules);

      if ($validator->fails()) {
          return Response::json(['error'=> 'Please enter a reply.']);
      } else {

        $replyBody = $request->json('replyBody');
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
    $id = $request->json('replyID');
    $reply = Freply::find($id);
    if($user->role == 1 || $user->id == $reply->replyAuthor)
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

  public function getUser(Request $request, $name)
  {
    $user = User::where('users.name', '=', $name)->where('users.ban', '=', 0)->join('roles', 'users.role', '=', 'roles.id')->select('users.id', 'users.name', 'users.displayName', 'users.avatar', 'roles.roleName', 'users.topics', 'users.replies', 'users.created_at')->first();

    if(!empty($user))
    {
      return Response::json($user);
    }
    else
    {
      return Response::json(['error'=> 'Not an active user.']);
    }
  }

  public function updateProfile(Request $request)
  {
    $id = Auth::user()->id;
    $profile = User::find($id);

    $displayName = Purifier::clean($request->input('displayName'));
    $email = Purifier::clean($request->input('email'));
    $avatar = $request->file('avatar');
    $password = Purifier::clean($request->input('password'));
    $confirm = Purifier::clean($request->input('confirm'));
    $emailReply = Purifier::clean($request->input('emailReply'));
    $emailDigest = Purifier::clean($request->input('emailDigest'));

    if($displayName == NULL)
    {
      $displayName = $profile->name;
    }
    else {
      $profile->displayName = $displayName;
    }
    if($email != NULL)
    {
      $profile->email = $email;
    }
    if($emailReply != NULL)
    {
      $profile->website = $emailReply;
    }
    if($emailDigest != NULL)
    {
      $profile->emailDigest = $emailDigest;
    }

    if($avatar != NULL)
    {

      if(File::size($avatar) > 2000)
      {
        return Response::json(['error'=> 'Avatar too large.']);
      }
      else {

        $imageFile = 'storage/media/users/avatars';

        if (!is_dir($imageFile)) {
          mkdir($imageFile,0777,true);
        }

        $fileName = str_random(8);
        $avatar->move($imageFile, $fileName.'.png');
        $avatar = $imageFile.'/'.$fileName.'png';

        if (extension_loaded('fileinfo')) {
          $img = Image::make($avatar);

          list($width, $height) = getimagesize($avatar);
          if($width > 200)
          {
            $img->resize(200, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            if($height > 200)
            {
              $img->crop(200, 200);
            }
          }
          $img->save($avatar);
        }

        $profile->avatar = $avatar;
      }
    }
    if($password != NULL)
    {
      if($password === $confirm)
      {
        $password = Hash::make($password);
        $profile->password = $password;
      } else {
        return Response::json(['error'=> 'Username/password do not match.']);
      }
    }

    $profile->save();

    $userData = User::where('users.id', '=', $profile->id)->join('roles', 'users.role', '=', 'roles.id')->select('users.displayName', 'users.email', 'users.avatar')->first();
    return Response::json($userData);
  }

public function deactivateUser()
{
  $user = Auth::user();
  $user = User::find($user->id);

  $user->activated == 0;
  $user->save();

  return Response::json(['success'=> 'User has been deactivated.']);
}


public function search(Request $request)
{
  $rules = array(
    'searchContent' => 'required'
  );
  $validator = Validator::make($request->json()->all(), $rules);

  if ($validator->fails()) {
    return Response::json(['error'=> 'Please enter search content.']);
  } else {

    $searchContent = Purifier::clean($request->json('searchContent'));

    $result = Ftopic::where('ftopics.topicTitle', 'LIKE', '%'.$searchContent.'%')->join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id')->orderBy('ftopics.updated_at', 'DESC')->select('ftopics.id', 'ftopics.topicTitle', 'ftopics.topicSlug', 'ftopics.created_at', 'ftopics.updated_at', 'ftopics.topicReplies', 'ftopics.topicViews', 'ftopics.topicChannel', 'fchannels.channelTitle')->paginate(25);

    return Response::json($result);
  }
}

public function createTopic(Request $request)
{
  $user = Auth::user();
  $channels = Fchannel::where('channelArchived', '=', 0)->select('id', 'channelTitle')->get();

  return Response::json($channels);

  return Response::json($channels);
}


public function storeTopic(Request $request)
{
  $user = Auth::user();
  $validator = Validator::make($request->json()->all(), [
    'topicTitle'  =>  'required',
    'topicChannel' => 'required',
    'topicBody' => 'required'
  ]);

  if ($validator->fails()) {
    return Response::json(['error'=> 'Please fill out all topic fields.']);
  }
  else {

    $topicTitle = $request->json('topicTitle');
    $topicBody = Purifier::clean($request->json('topicBody'));
    $topicChannel = $request->json('topicChannel');
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

    if (Ftopic::where('topicSlug', '=', $topicSlug)->exists()) {
       $topicSlug = $topicSlug.'_'.mt_rand(1, 9999);
    }

    $pastTopics = Ftopic::where('topicAuthor', '=', $user->id)->select('id', 'created_at')->orderBy('id', 'DESC')->skip(5)->take(1)->first();
    $currentTime = date('Y-m-d H:i:s');

    if(!empty($pastTopics) && $user->role != 1)
    {
      $datetime1 = new DateTime($pastTopics->created_at);
      $datetime2 = new DateTime($currentTime);
      $interval = $datetime1->diff($datetime2);

      if($interval->format('%a%H') < 1) {
        return Response::json(['sorry'=> 'Please wait to enter another topic.']);
      }
    }

    if(strlen($topicBody) > 1500)
    {
      return Response::json(['sorry'=> 'Please limit your entry to 1500 characters.']);
    }
    else {

      $topicBody = Markdown::convertToHtml($topicBody);
      $topicBody = Purifier::clean($topicBody);

      $converter = new HtmlConverter();
      $topicBody = $converter->convert($topicBody);

      if(substr_count($topicBody, 'img') > 5 || substr_count($topicBody, 'href') > 5 || substr_count($topicBody, 'youtube.com') > 2)
      {
        return Response::json(['error'=> 'Please dont spam, too many links.']);
      }
      else {
        $topic = new Ftopic;

        $topic->topicTitle = $topicTitle;
        $topic->topicBody = $topicBody;
        $topic->topicChannel = $topicChannel;
        $topic->topicSlug = $topicSlug;
        $topic->topicViews = 0;
        $topic->topicReplies = 0;
        $topic->topicAuthor = Auth::user()->id;
        $topic->topicArchived = 0;
        $topic->topicFeature = 0;
        $topic->allowReplies = $allowReplies;
        $topic->save();

        $channelCount = Fchannel::where('id', '=', $topicChannel)->increment('channelTopics');
        $userCount = User::where('name', '=', $user->name)->increment('topics');

        $topicData = Ftopic::where('ftopics.id', '=', $topic->id)->join('users', 'ftopics.topicAuthor', '=', 'users.id')->join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id')->select('ftopics.id', 'ftopics.topicTitle', 'ftopics.topicBody', 'ftopics.topicChannel', 'ftopics.topicSlug', 'ftopics.topicViews', 'ftopics.topicReplies', 'ftopics.topicAuthor', 'ftopics.topicFeature',  'ftopics.allowReplies', 'ftopics.created_at', 'ftopics.updated_at', 'users.id', 'users.avatar', 'users.displayName', 'users.name', 'fchannels.channelTitle')->first();
        return Response::json($topicData)->setCallback($request->input('callback'));
      }
    }
  }
}

public function updateTopic(Request $request, $id)
{
  $user = Auth::user();
  $topic = Ftopic::find($id);

  if($user->role == 1 || $user->id == $topic->topicAuthor)
  {
    $rules = array(
      'topicTitle'		=> 	'required',
      'topicChannel' => 'required',
      'topicBody' => 'required'
    );
    $validator = Validator::make($request->json->all(), $rules);

    if ($validator->fails()) {
        return Response::json(['error'=> 'Please fill out all topic fields.']);
    } else {

      $topicTitle = $request->json('topicTitle');
      $topicBody = Purifier::clean($request->json('topicBody'));
      $topicChannel = $request->json('topicChannel');
      $allowReplies = 1;

      if($topicTitle != NULL)
      {
        if (preg_match('/[A-Za-z]/', $topicTitle) || preg_match('/[0-9]/', $topicTitle)) {
          $topicSlug = str_replace(' ', '-', $topicTitle);
          $topicSlug = preg_replace('/[^A-Za-z0-9\-]/', '', $topicSlug);
          $topicSlug = preg_replace('/-+/', '-', $topicSlug);

          if (Ftopic::where('topicSlug', '=', $topicSlug)->where('id', '!=', $topic->id)->exists()) {
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
        $pastTopics = Ftopic::where('topicAuthor', '=', $user->id)->select('id', 'created_at')->orderBy('id', 'DESC')->skip(5)->take(1)->first();
        $currentTime = date('Y-m-d H:i:s');

        if(!empty($pastTopics) && $user->role != 1)
        {
          $datetime1 = new DateTime($pastTopics->created_at);
          $datetime2 = new DateTime($currentTime);
          $interval = $datetime1->diff($datetime2);

          if($interval->format('%a%H') < 1) {
            return Response::json(['sorry'=> 'Please wait to make another update.']);
          }
        }

        if(strlen($topicBody) > 1500)
        {
          return Response::json(['error'=> 'Please limit your entry to 1500 characters.']);
        }
        else {

          $topicBody = Markdown::convertToHtml($topicBody);
          $topicBody = Purifier::clean($topicBody);

          $converter = new HtmlConverter();
          $topicBody = $converter->convert($topicBody);

          if(substr_count($topicBody, 'img') > 5 || substr_count($topicBody, 'href') > 5 || substr_count($topicBody, 'youtube.com') > 2)
          {
            return Response::json(['error'=> 'Too many links, please do not spam.']);
          }
          else {
            $topic->topicBody = $topicBody;
          }
        }
      }
      if($topicChannel != NULL)
      {
        if($topic->topicChannel != $topicChannel)
        {
          Fchannel::where('id', '=', $topic->topicChannel)->decrement('channelTopics');
          Fchannel::where('id', '=', $topicChannel)->increment('channelTopics');
        }
        $topic->topicChannel = $topicChannel;
      }

      $topic->allowReplies = $allowReplies;
      $topic->save();

      $topicData = Ftopic::where('ftopics.id', '=', $topic->id)->join('users', 'ftopics.topicAuthor', '=', 'users.id')->join('fchannels', 'ftopics.topicChannel', '=', 'fchannels.id')->select('ftopics.id', 'ftopics.topicTitle', 'ftopics.topicBody', 'ftopics.topicChannel', 'ftopics.topicSlug', 'ftopics.topicViews', 'ftopics.topicReplies', 'ftopics.topicAuthor', 'ftopics.topicFeature',  'ftopics.allowReplies', 'ftopics.created_at', 'ftopics.updated_at', 'users.id', 'users.avatar', 'users.displayName', 'users.name', 'fchannels.channelTitle')->first();
      return Response::json($topicData);
    }
  } else {
    return Response::json(['error'=> 'Unable to update.']);
  }
}

public function deleteTopic(Request $request, $id)
{
  $user = Auth::user();
  $topic = Ftopic::find($id);

  if($user->role == 1 || $user->id == $topic->topicAuthor)
  {
    $replies = Freply::where('topicID', '=', $topic->id)->get();
    $user = User::where('id', '=', $topic->topicAuthor)->first();
    $channel = Fchannel::where('id', '=', $topic->topicChannel)->first();

    if($user->topics > 0)
    {
      $user->topics = $user->topics - 1;
      $user->save();
    }

    if($channel->channelTopics > 0)
    {
      $channel->channelTopics = $channel->channelTopics - 1;
      $channel->save();
    }

    if(!empty($replies))
    {
      foreach($replies as $key => $value)
      {

        $user = User::where('id', '=', $value->replyAuthor)->first();
        if($user->replies > 0)
        {
          $user->replies = $user->replies - 1;
          $user->save();
        }


        $value->delete();
      }
    }
    $topic->delete();

    return Response::json(['success'=> 'Topic removed.']);
  } else {
    return Response::json(['error'=> 'Unable to remove item.']);
  }
}

public function storeChannel(Request $request)
{
  $user = Auth::user();
  if($user->role == 1)
  {
    $rules = array(
      'channelTitle'	=> 	'required'
    );
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return Response::json(['error'=> 'Please enter a channel title.']);
    } else {

      $channelTitle = $request->input('channelTitle');
      $channelDesc = $request->input('channelDesc');
      $channelImg = $request->file('channelImg');

      if (preg_match('/[A-Za-z]/', $channelTitle) || preg_match('/[0-9]/', $channelTitle)) {
        $channelSlug = str_replace(' ', '-', $channelTitle);
        $channelSlug = preg_replace('/[^A-Za-z0-9\-]/', '', $channelSlug);
        $channelSlug = preg_replace('/-+/', '-', $channelSlug);

        if (Fchannel::where('channelSlug', '=', $channelSlug)->exists()) {
           $channelSlug = $channelSlug.'_'.mt_rand(1, 9999);
        }
        if(empty($channelDesc))
        {
          $channelDesc = "No Description";
        }
        if(empty($channelImg))
        {
          $channelImg = preg_replace('/[^A-Z]/i', "" ,$channelTitle);
          $channelImg = substr($channelImg, 0, 2);
          $channelImg = "https://invatar0.appspot.com/svg/".$channelImg.".jpg?s=100";
        }
        elseif(!empty($channelImg))
        {
          $imageFile = 'storage/media/channels';

          if (!is_dir($imageFile)) {
            mkdir($imageFile,0777,true);
          }

          $ext = $channelImg->getClientOriginalExtension();
          $channelImg->move($imageFile, $channelSlug.'.png');
          $channelImg = $imageFile.'/'.$channelSlug.'.png';

          if (extension_loaded('fileinfo')) {
            $img = Image::make($channelImg);
            list($width, $height) = getimagesize($channelImg);
            if($width > 250)
            {
              $img->resize(250, null, function ($constraint) {
                  $constraint->aspectRatio();
              });
            }
            $img->save($channelImg);
          }
        }

        $channel = new Fchannel;

        $channel->channelTitle = $channelTitle;
        $channel->channelDesc = $channelDesc;
        $channel->channelImg = $channelImg;
        $channel->channelSlug = $channelSlug;
        $channel->channelArchived = 0;
        $channel->channelTopics = 0;
        $channel->save();

        $channelData = Fchannel::where('id', '=', $channel->id)->select('id', 'channelTitle', 'channelDesc', 'channelImg', 'channelTopics', 'channelSlug', 'created_at')->first();
        return Response::json($channelData);
      } else {
        return Response::json(['error'=> 'Unable to create channel.']);
      }
    }
  } else {
    return Response::json(['error'=> 'Unauthorized user.']);
  }
}

public function updateChannel(Request $request, $id)
{
  $user = Auth::user();
  if($user->role == 1)
  {
    $rules = array(
      'channelTitle'		=> 	'required'
    );
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return Response::json(['error'=> 'Please enter a channel title.']);
    } else {

      $channel = Fchannel::find($id);

      $channelTitle = $request->input('channelTitle');
      $channelDesc = $request->input('channelDesc');
      $channelImg = $request->file('channelImg');

      if($channelTitle != NULL)
      {
        if (preg_match('/[A-Za-z]/', $channelTitle) || preg_match('/[0-9]/', $channelTitle)) {
          $channelSlug = str_replace(' ', '-', $channelTitle);
          $channelSlug = preg_replace('/[^A-Za-z0-9\-]/', '', $channelSlug);
          $channelSlug = preg_replace('/-+/', '-', $channelSlug);

          if (Fchannel::where('channelSlug', '=', $channelSlug)->exists()) {
             $channelSlug = $channelSlug.'_'.mt_rand(1, 9999);
          }

          $channel->channelTitle = $channelTitle;
          $channel->channelSlug = $channelSlug;
        } else {
          return Response::json(['error'=> 'Channel does not exist.']);
        }
      }

      if($channelDesc != NULL)
      {
        $channel->channelDesc = $channelDesc;
      }
      else {
        $channel->channelDesc = "No Description.";
      }

      if($channelImg != NULL)
      {

        $imageFile = 'storage/media/channels';

        if (!is_dir($imageFile)) {
          mkdir($imageFile,0777,true);
        }

        $ext = $channelImg->getClientOriginalExtension();
        $channelImg->move($imageFile, $channelSlug.'.'.$ext);
        $channelImg = $imageFile.'/'.$channelSlug.'.'.$ext;

        if (extension_loaded('fileinfo')) {
          $img = Image::make($channelImg);
          list($width, $height) = getimagesize($channelImg);
          if($width > 250)
          {
            $img->resize(250, null, function ($constraint) {
                $constraint->aspectRatio();
            });
          }
          $img->save($channelImg);
        }

        $channel->channelImg = $channelImg;
      }
      elseif(empty($channelImg) && $channel->channelImg == NULL)
      {
        {
          $channelImg = preg_replace('/[^A-Z]/i', "" ,$channelTitle);
          $channelImg = substr($channelImg, 0, 2);
          $channelImg = "https://invatar0.appspot.com/svg/".$channelImg.".jpg?s=100";
        }
      }

      $channel->save();

      $channelData = Fchannel::where('id', '=', $channel->id)->select('id', 'channelTitle', 'channelDesc', 'channelImg', 'channelTopics', 'channelSlug', 'created_at')->first();
      return Response::json($channelData);
    }
  } else {
    return Response::json(['error'=> 'Unauthorized user.']);
  }
}

public function deleteChannel(Request $request, $id)
{
  $user = Auth::user();
  if($user->role == 1)
  {
    $channel = Fchannel::find($id);

    if($channel->id != 1)
    {
      $topics = Ftopic::where('topicChannel', '=', $channel->id)->get();
      if(!$topics->isEmpty())
      {
        foreach($topics as $key => $value)
        {
          $value->topicChannel = 1;
          $value->save();
        }
      }
      $channel->delete();

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
