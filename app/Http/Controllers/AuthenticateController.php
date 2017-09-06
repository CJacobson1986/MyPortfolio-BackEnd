<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\User;
use App\Role;
use App\Option;
use App\Subscription;
use \Input;
use \Response;
use \Image;
use \Auth;
use \Redirect;
use \DB;
use \Purifier;

class AuthenticateController extends Controller
{
  public function __construct()
  {
    $this->middleware('jwt.auth', ['except' => ['authenticate', 'getAuth', 'doSignUp', 'confirmToken', 'storeSubscription', 'confirmSubscription', 'refreshToken']]);
  }

 public function index()
 {

 }

  public function doSignUp(Request $request)
  {
    $rules = [
      'email'	        => 	'required',
      'username'			=>	'required',
      'password'			=>	'required'
    ];
    $validator = Validator::make(Purifier::clean($request->json()->all()), $rules);

    if ($validator->fails()) {
      return Response::json(['error' => 'Please fill out all fields.']);
    } else {

      $options = Option::find(1);

      if($options->allowRegistration == 1)
      {
        $sub = substr($username, 0, 2);

        if(empty($fullName))
        {
          $fullName = $username;
        }

        $userCheck = User::where('email', '=', $email)->orWhere('name', '=', $username)->select('email', 'name')->first();

        if(empty($userCheck))
        {

          $role = Role::find(2);

          $user = new User;
          $user->email = $email;
          $user->name = $username;
          $user->password = Hash::make($password);
          $user->displayName = $fullName;
          $user->avatar = "https://invatar0.appspot.com/svg/".$sub.".jpg?s=100";

          $options = Option::first();
          $website = $options->website;
          $url = $options->baseurl;




  public function confirmToken(Request $request)
  {
    $token = Purifier::clean($request->json('token'));
    $user = User::where('activation_token','=',$token)->first();

    if(!empty($user))
    {
      if($user->activated == 0)
      {
        $user->activated = 1;
        $user->save();
        //Success
        return Response::json(['success' => 'Thanks for signing up.']);
      }
      else {
        //User Activated already
        return Response::json(['error'=> 'Email already used.']);
      }
    } else {
      //User not found
      return Response::json(['error'=> 'User not found']);
    }
  }

  public function authenticate(Request $request)
  {
      $email = $request->json('email');
      $password = $request->json('password');
      $hash = Hash::make($password);
      $options = Option::find(1);
      $userCheck = User::where('email', '=', $email)->first();
      if(!empty($userCheck))
      {
        $cred = array("email", "password");
        $credentials = compact("email", "password", $cred);
        try {
          if (! $token = JWTAuth::attempt($credentials)) {
              return response()->json(['error' => 'invalid_credentials'], 401);
          }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }
        if($userCheck->ban == 1) {
          //User is banned
          return Response::json(['So Sorry' => 'You have been banned.']);
        }
        else {
          if($options->requireActivation == 1 && $userCheck->activated == 0)
          {
            //Require Activation
            return Response::json(['error' => 'User not active.']);
          }
          else {
            return Response::json(compact('token'))->setCallback($request->input('callback'));
          }
        }
      } else {
        //User not found
        return Response::json(['error' => 'User not found.']);
      }
  }

  public function getAuthenticatedUser(Request $request)
  {
      try {
        if (! $user = JWTAuth::parseToken()->authenticate()) {
          return response()->json(['user_not_found'], 404);
        }
      } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
        return response()->json(['token_expired'], $e->getStatusCode());
      } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
        return response()->json(['token_invalid'], $e->getStatusCode());
      } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
        return response()->json(['token_absent'], $e->getStatusCode());
      }
      return response()->json(compact('user'))->setCallback($request->input('callback'));
  }

  public function refreshToken(Request $request) {
    $token = JWTAuth::getToken();
    if(!$token){
        throw new BadRequestHtttpException('Token not provided');
    }
    try{
        $token = JWTAuth::refresh($token);
    }catch(TokenInvalidException $e){
        throw new AccessDeniedHttpException('The token is invalid');
    }

    return Response::json($token)->setCallback($request->input('callback'));
  }
}
