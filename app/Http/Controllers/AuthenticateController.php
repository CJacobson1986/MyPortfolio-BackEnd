<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;
use Hash;
use App\User;
use App\Role;
use App\Option;
use \Response;
use \Image;
use \Auth;
use \DB;
use \Purifier;

class AuthenticateController extends Controller
{
  public function __construct()
  {
    $this->middleware('jwt.auth', ['except' => ['authenticate', 'getAuth', 'doSignUp', 'confirmToken', 'storeSubscription', 'confirmSubscription', 'refreshToken']]);
  }

  public function doSignUp(Request $request)
  {
    $rules = [
      'email'	        => 	'required',
      'username'			=>	'required',
      'password'			=>	'required'
    ];
    $validator = Validator::make(Purifier::clean($request->all()), $rules);

    if ($validator->fails()) {
      return Response::json(['error' => 'Please fill out all fields.']);
    } else {

      $options = Option::find(1);


        $email = $request->input('email');
        $username = $request->input('username');
        $fullname = $request->input('fullname');
        $password = $request->input('password');

        $username = preg_replace('/[^0-9A-Z]/i',"",$username);
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
          $user->avatar = "https://invatar0.appspot.com/svg/".$sub.".jpg?s=100";
          $user->roleID = 2;
          $user->save();
          return Response::json(['success'=>"Thank you for signing up."]);
        } else {
          if($userCheck->email === $email)
          {
            //Email Already Registered
            return Response::json(['error'=> 'Email already registered.']);
          }
          elseif($userCheck->name === $username)
          {
            //Username already Registered
            return Response::json(['error'=> 'Please choose another username.']);
          }
        }
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
              return response()->json(['error' => 'invalid_credentials']);
          }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token']);
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
            return Response::json(compact('token'));
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
}
