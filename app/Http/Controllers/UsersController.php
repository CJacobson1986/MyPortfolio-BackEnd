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
    $this->middleware('jwt.auth', ['except' => ['getUsers']]);
  }

  public function getUsers(Request $request)
  {
    $user = Auth::user();

    $users = User::join('roles', 'users.role', '=', 'roles.id')->select('users.id', 'users.name', 'users.displayName', 'users.email', 'users.avatar', 'users.topics', 'users.replies', 'users.role', 'users.activated', 'users.ban', 'users.last_login', 'users.created_at', 'roles.roleName')->get();
    $roles = Role::select('id', 'roleName', 'roleSlug', 'roleDesc', 'roleCount')->get();

    return Response::json(['users' => $users, 'roles' => $roles]);
  }

  public function editUser(Request $request, $id)
  {
    $user = Auth::user();
    if($user->role == 1)
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
    if($user->role == 1)
    {
      $user = User::find($id);
      $options = Option::find(1);
      if($user->id != $options->owner)
      {
        if($user->ban == 0)
        {
          $user->ban = 1;
          $user->save();
          return Response::json(['success'=> 'User has been placed on the ban list.']);
        }
        else {
          $user->ban = 0;
          $user->save();
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
    if($user->role == 1)
    {
      $user = User::find($id);
      $options = Option::find(1);
      if($user->id != $options->owner)
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
    if($user->role == 1)
    {
      $profile = User::find($id);

      $displayName = $request->input('displayName');
      $email = $request->input('email');
      $avatar = $request->file('avatar');
      $password = $request->input('password');
      $confirm = $request->input('confirm');
      $emailReply = $request->input('emailReply');
      $emailDigest = $request->input('emailDigest');

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

        $imageFile = 'storage/media/users/avatars';

        if (!is_dir($imageFile)) {
          mkdir($imageFile,0777,true);
        }

        $ext = $avatar->getClientOriginalExtension();
        $fileName = str_random(8);
        $avatar->move($imageFile, $fileName.'.'.$ext);
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
          $profile->password = $password;
        } else {
          return Response::json(['error'=> 'Unauthorized user.']);
        }
      }

      $profile->save();

      $userData= User::where('users.id', '=', $profile->id)->join('roles', 'users.role', '=', 'roles.id')->select('users.id', 'users.name', 'users.displayName', 'users.email', 'users.location', 'users.website', 'users.aboutMe', 'users.profileTitle', 'users.avatar', 'users.topics', 'users.replies', 'users.role', 'users.activated', 'users.ban', 'users.last_login', 'users.created_at', 'roles.roleName')->first();
      return Response::json(['success'=> 'Your profile has been updated.']);

    } else {
      return Response::json(['error'=> 'Unable to update profile.']);
    }
  }

  public function storeRole(Request $request)
  {
    $user = Auth::user();
    if($user->role == 1)
    {
      $rules = array(
        'roleName'		=> 	'required'
      );
      $validator = Validator::make($request->json()->all(), $rules);

      if ($validator->fails()) {
          return Response::json(['error'=> 'Please fill out role.']);
      } else {

        $roleName = $request->json('roleName');
        $roleDesc = $request->json('roleDesc');
        $roleSlug = preg_replace("/ /","+",$roleName);

        if (Role::where('roleSlug', '=', $roleSlug)->exists()) {
           $roleSlug = $roleSlug.'_'.mt_rand(1, 9999);
        }
        if(empty($roleDesc))
        {
          $roleDesc = "No Description";
        }

        $role = new Role;

        $role->roleName = $roleName;
        $role->roleDesc = $roleDesc;
        $role->roleSlug = $roleSlug;
        $role->roleCount = 0;
        $role->save();

        $roleData = Role::where('id', '=', $role->id)->select('id', 'roleName', 'roleSlug', 'roleDesc', 'roleCount')->first();
        return Response::json($roleData);
      }
    } else {
      return Response::json(['error'=> 'Unable to store, unexpected error.']);
    }
  }

  public function editRole(Request $request, $id)
  {
    $user = Auth::user();
    if($user->role == 1)
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
    if($user->role == 1)
    {
      $rules = array(
        'roleName'		=> 	'required'
      );
      $validator = Validator::make($request->json()->all(), $rules);

      if ($validator->fails()) {
          return Response::json(['error'=> 'Please enter all fields.']);
      } else {

        $role = Role::find($id);
        $roleName = $request->json('roleName');
        $roleDesc = $request->json('roleDesc');

        $roleCheck = Role::where('roleName', '=', $roleName)->first();
        if(empty($roleCheck))
        {
          $role->roleName = $roleName;
          $roleSlug = preg_replace("/ /","+",$roleName);
          if (Role::where('roleSlug', '=', $roleSlug)->exists()) {
             $roleSlug = $roleSlug.'+'.mt_rand(1, 9999);
          }
          $role->roleSlug = $roleSlug;
          $role->roleDesc = $roleDesc;
          $role->save();
          if(empty($roleDesc))
          {
            $roleDesc = "No Description";
          }
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
    if($user->role == 1)
    {
      $role = Role::find($id);

      if($role->id != 1 && $role->id != 2)
      {
        $users = User::where('role', '=', $role->roleName)->get();
        $newRole = Role::find(2);
        if($users->isEmpty())
        {
          foreach($users as $key => $value)
          {
            $value->role = $newRole->roleName;
            $value->save();
          }
        }
        $role->delete();
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

    if($user->role == 1)
    {
      $user = User::find($id);
      $role = $request->json('roleID');

      $roleCheck = Role::where('id', '=', $role)->first();
      if(!empty($roleCheck))
      {
        $option = Option::find(1);
        if($user->id == $option->owner)
        {
          //Role Cannot be changed.
          return Response::json(['error'=> 'Unable to change role.']);
        } else {
          $user->role = $role;
          $user->save();
          //Success
          $userData = User::where('users.id', '=', $user->id)->join('roles', 'users.role', '=', 'roles.id')->select('users.id', 'users.name', 'users.displayName', 'users.email', 'users.location', 'users.website', 'users.aboutMe', 'users.profileTitle', 'users.avatar', 'users.topics', 'users.replies', 'users.role', 'users.activated', 'users.ban', 'users.last_login', 'users.created_at', 'roles.roleName')->first();
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
