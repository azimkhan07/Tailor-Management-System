<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(){
        $title = "users";
        $users = User::get();
        return view('users',compact('title','users'));
    }

    public function store(Request $request){
        $this->validate($request,[
            'username'=>'required',
            'name' => 'required',
            'email' => 'required',
            'password' => 'required|max:200|confirmed',
            'avatar' =>'nullable|file|image|mimes:jpg,jpeg,png,gif',
        ]);

        $imageName = null;
        if($request->hasFile('avatar')){
            $imageName = time().'.'.$request->avatar->extension();
            $this->storeAvatar($request->avatar, $imageName);
        }

        User::create([
            'name'=>$request->name,
            'username'=>$request->username,
            'email'=>$request->email,
            'avatar'=>$imageName,
            'password'=>Hash::make($request->password),
        ]);
        $notification = array(
            'message'=>"New user added successfully!!",
            'alert-type'=>'success'
        );
        return back()->with($notification);
    }

    public function profile(){
        $title = "user profile";
        return view('user-profile',compact('title'));
    }

    public function updateProfile(Request $request){
        $this->validate($request,[
            'username'=>'required',
            'name' => 'required',
            'email' => 'required',
            'avatar' =>'nullable|file|image|mimes:jpg,jpeg,png,gif',
        ]);
        $data = [
            'username'=>$request->username,
            'name'=>$request->name,
            'email'=>$request->email,
        ];
        if($request->hasFile('avatar')){
            $imageName = time().'.'.$request->avatar->extension();
            $this->storeAvatar($request->avatar, $imageName);
            $data['avatar'] = $imageName;
        }
        auth()->user()->update($data);
        $notification = array(
            'message'=>"User profile updated successfully!!",
            'alert-type'=>'success'
        );
        return back()->with($notification);
    }

    public function updatePassword(Request $request){
        $this->validate($request,[
            'old_password'=>'required',
            'password'=>'required|max:200|confirmed',
        ]);
        
        if (password_verify($request->old_password,auth()->user()->password)){
            auth()->user()->update(['password'=>Hash::make($request->password)]);
            $notification = array(
                'message'=>"User password updated successfully!!!",
                'alert-type'=>'success'
            );
        }else{
            $notification = array(
                'message'=>"Old Password do not match!!!",
                'alert-type'=>'error'
            );
        }
        return back()->with($notification);
    }

    public function destroy(Request $request){
        $user = User::find($request->id);
        if ($user != auth()->user()){
            $user->delete();
            $notification = array(
                'message'=>"User deleted successfully!!",
                'alert-type'=>'success'
            );
        }else{
            $notification = array(
                'message'=>"Cannot delete current authenticated user!!!",
                'alert-type'=>'error'
            );
        }
        
        return back()->with($notification);
    }

    /**
     * Move an uploaded avatar into the public storage/avatars directory.
     */
    protected function storeAvatar($avatar, string $imageName): void
    {
        $directory = public_path('storage/avatars');

        if (! file_exists($directory)) {
            mkdir($directory, 0775, true);
        }

        $avatar->move($directory, $imageName);
    }
}
