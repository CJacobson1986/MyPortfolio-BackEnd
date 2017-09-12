<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use JWTAuth;
use Auth;
use App\User;
use App\Role;
use App\Option;
use \Input;
use \Response;
use \Image;
use \Session;
use \Redirect;

class UsersController extends Controller
{
  public function __construct()
  {
    $this -> middleware('jwt.auth', ['except' => ['getUsers']]);
  }

  public function getUsers(Request $request)
  {
    $user = Auth::user();

    $users = User::join('roles', 'users.roleID', '=', 'roles.id') -> select('users.id', 'users.name', 'users.email', 'users.avatar', 'users.roleID', 'users.activated', 'users.ban', 'roles.roleName') -> get();
    $roles = Role::select('id', 'roleName', 'roleSlug', 'roleDesc', 'roleCount') -> get();

    return Response::json(['users' => $users, 'roles' => $roles]);
  }

  public function getUser(Request $request, $name)
  {
    $user = User::where('users.name', '=', $name) -> where('users.ban', '=', 0) -> join('roles', 'users.roleID', '=', 'roles.id') -> select('users.id', 'users.name', 'users.avatar', 'roles.roleName') -> first();

    if(!empty($user))
    {
      return Response::json($user);
    }
    else
    {
      return Response::json(['error'=> 'Not an active user.']);
    }
  }

public function deactivateUser()
{
  $user = Auth::user();
  $user = User::find($user->id);

  $user -> activated == 0;
  $user -> save();

  return Response::json(['success'=> 'User has been deactivated.']);
}

  public function editUser(Request $request, $id)
  {
    $user = Auth::user();
    if($user->roleID == 1)
    {
      $user = User::find($id);

      return Response::json($user);
    } else {
      return Response::json(['error'=> 'Unable to edit user.']);
    }
  }

  public function banUser(Request $request, $id)
  {
    $user = Auth::user();
    if($user -> roleID == 1)
    {
      $user = User::find($id);
      $options = Option::find(1);
      if($user -> id != $options->owner)
      {
        if($user -> ban == 0)
        {
          $user -> ban = 1;
          $user -> save();
          return Response::json(['success'=> 'User has been placed on the ban list.']);
        }
        else {
          $user -> ban = 0;
          $user -> save();
          return Response::json(['success'=> 'User has been removed from the ban list.']);
        }
      }
      else {
        return Response::json(['error'=> 'Unauthorized user.']);
      }
    } else {
      return Response::json(['error'=> 'Unauthorized user.']);
    }
  }

  public function deleteUser(Request $request, $id)
  {
    $user = Auth::user();
    if($user -> roleID == 1)
    {
      $user = User::find($id);
      $options = Option::find(1);
      if($user -> id != $options -> owner)
      {
        $user->delete();
        return Response::json(['success'=> 'User has been removed.']);
      }
      else {
        return Response::json(['error'=> 'Unauthorized user.']);
      }
    } else {
      return Response::json(['error'=> 'Unauthorized user.']);
    }
  }

  public function updateProfile(Request $request, $id)
  {
    $user = Auth::user();
    if($user -> roleID == 1)
    {
      $profile = User::find($id);

      $email = $request->input('email');
      $avatar = $request->file('avatar');
      $password = $request->input('password');
      $confirm = $request->input('confirm');
      //$emailDigest = $request->input('emailDigest');
      //Need to add code for the weekly/monthly auto-email system


      if($email != NULL)
      {
        $profile -> email = $email;
      }
      if($avatar != NULL)
      {

        $imageFile = 'storage/media/users/avatars';

        if (!is_dir($imageFile)) {
          mkdir($imageFile,0777,true);
        }

        $ext = $avatar -> getClientOriginalExtension();
        $fileName = str_random(8);
        $avatar -> move($imageFile, $fileName.'.'.$ext);
        $avatar = $imageFile.'/'.$fileName.'.'.$ext;

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

      if($password != NULL)
      {
        if($password === $confirm)
        {
          $password = Hash::make($password);
          $profile -> password = $password;
        } else {
          return Response::json(['error'=> 'Unauthorized user.']);
        }
      }

      $profile->save();

      $userData = User::where('users.id', '=', $profile->id) -> join('roles', 'users.roleID', '=', 'roles.id') -> select('users.id', 'users.name', 'users.email', 'users.avatar', 'users.roleID', 'users.ban') -> first();
      return Response::json(['success'=> 'Your profile has been updated.']);

    } else {
      return Response::json(['error'=> 'Unable to update profile.']);
    }
  }

  public function storeRole(Request $request)
  {
    $user = Auth::user();
    if($user -> roleID == 1)
    {
      $rules = array(
        'name' => 'required'
      );
      $validator = Validator::make($request->all(), $rules);

      if ($validator -> fails()) {
          return Response::json(['error'=> 'Please fill out name.']);
      } else {

        $roleName = $request -> input('name');

        $role = new Role;

        $role -> name = $roleName;
        $role -> save();

        $roleData = Role::where('id', '=', $role->id) -> select('id') -> first();
        return Response::json($roleData);
      }
    } else {
      return Response::json(['error'=> 'Unable to store, unexpected error.']);
    }
  }

  public function editRole(Request $request, $id)
  {
    $user = Auth::user();
    if($user -> roleID == 1)
    {
      $role = Role::find($id);

      return Response::json(['success'=> 'Role has been edited.']);
    } else {
      return Response::json(['error'=> 'Unauthorized user.']);
    }
  }

  public function updateRole(Request $request, $id)
  {
    $user = Auth::user();
    if($user -> roleID == 1)
    {
      $rules = array(
        'name' => 'required'
      );
      $validator = Validator::make($request->all(), $rules);

      if ($validator -> fails()) {
          return Response::json(['error'=> 'Please enter all fields.']);
      } else {

        $role = Role::find($id);
        $role -> name = $roleName;
        $roleCheck = Role::where('name', '=', $roleName)->first();
        if(empty($roleCheck))
        {
          $role->save();
        }
        return Response::json($role);
      }
    } else {
      return Response::json(['error'=> 'Unable to edit role.']);
    }
  }

  public function deleteRole(Request $request, $id)
  {
    $user = Auth::user();
    if($user -> roleID == 1)
    {
      $role = Role::find($id);

      if($role -> id != 1 && $role -> id != 2)
      {
        $users = User::where('role', '=', $role -> roleName)->get();
        $newRole = Role::find(2);
        if($users->isEmpty())
        {
          foreach($users as $key => $value)
          {
            $value -> role = $newRole -> roleName;
            $value->save();
          }
        }
        $role -> delete();
        return Response::json(['success'=> 'Role deleted.']);
      }
      else {
        return Response::json(['error'=> 'Unable to delete role.']);
      }
    } else {
      return Response::json(['error'=> 'Unauthorized user.']);
    }
  }

  public function setRole(Request $request, $id)
  {
    $user = Auth::user();

    if($user -> roleID == 1)
    {
      $user = User::find($id);
      $role = $request -> json('roleID');

      $roleCheck = Role::where('id', '=', $role)->first();
      if(!empty($roleCheck))
      {
        $option = Option::find(1);
        if($user -> id == $option -> owner)
        {
          //Role Cannot be changed.
          return Response::json(['error'=> 'Unable to change role.']);
        } else {
          $user -> role = $role;
          $user -> save();
          //Success
          $userData = User::where('users.id', '=', $user->id) -> join('roles', 'users.roleID', '=', 'roles.id') -> select('users.id', 'users.name', 'users.email', 'users.avatar', 'users.roleID', 'users.ban') -> first();
          return Response::json($userData);
        }
      } else {
        //Role not found
        return Response::json(['error'=> 'Role not found.']);
      }
    } else {
      return Response::json(['error'=> 'Unauthorized user.']);
    }

  }
}
