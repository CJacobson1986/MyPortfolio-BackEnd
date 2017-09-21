<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Response;
use Purifier;

use App\Photo;

class PhotosController extends Controller
{
  public function store(Request $request)
  {
    $rules = [
      'photo' => 'required'
    ];
    $validator = Validator::make(Purifier::clean($request->all()), $rules);

    if($validator->fails())
    {
      return Response::json(['error' => 'Please fill out all fields.']);
    }

    $photoInput = $request->file('photo');
    $photoName = $photoInput->getClientOriginalName();
    $photoInput->move("storage/", $photoName);

    $photo = new Photo();
    $photo->photoURL = $request->root(). "/storage/".$photoName;
    $photo->save();

    return Response::json(['success' => 'Your Photo was Uploaded.']);
  }

  public function getPictures ()
  {
    $pictures = Photo::all();
    return Response::json(["pictures" => $pictures]);
  }
}
