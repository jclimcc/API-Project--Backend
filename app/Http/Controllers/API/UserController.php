<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => 
        [
            'login',
            'register',
            'refresh',
            'sendVerifyEmail',
            'verifyEmail',
            'forgetPassword',
            'resetPasswordLoad',
            'resetPassword',
        ]
    ]);
    }

    
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:50',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $input = request()->all();
        $input['password'] = Hash::make($input['password']);
        $user = User::create($input);
        
        $token = Auth::guard('api')->login($user);
        return response()->json([
            'success' => true, 'msg' => 'User Inserted Successfully',
            'user' => $user,
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ], 200);
    }

    //login api method call
    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $credentials = $request->only('email', 'password');

        $token = Auth::guard('api')->attempt($credentials);
        if (!$token) {
            return response()->json(['error' => 'Unauthorised'], 401);
        } else {
            return $this->responsedWithToken($token);
        }
    }
    public function logout()
    {
        try {
            Auth::guard('api')->logout();
            return response()->json(['msg' => 'User successfully signed out']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
    }
    public function refresh()
    {
        if(auth()->user())
        {
            return $this->responsedWithToken(auth()->refresh());
        }
        else
        {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
    }

    //update profile api method call
    public function updateProfile(Request $request)
    {
       
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'name' => 'required|string|min:3|max:50',
            'email' => 'required|string|email|unique:users,email,' . $request->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        try {
            $input = request()->all();
            $user = User::find($input['id']);
            if ($user->email != $input['email']) {
                $user->is_verified = 0;
            }
            
            $user->name = $input['name'];
            $user->email = $input['email'];
            $user->save();
            return response()->json(['success' => true, 'msg' => 'User Profile Updated Successfully', 'data' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 401);
        }
    }

    public function sendVerifyEmail($email){
        if(auth()->user()){
            $user = User::where('email',$email)->first();
            if($user){
                
                $data['email']=$email;
                $data['title']='Email Verification';
                $data['body']='Please click the below link to verify your email';
                $random = Str::random(40);
                $data['url']=URL::to('/').'/api/verify-email/'.$random;

                Mail::send('mail.verifyMail', ['data'=>$data], function($message) use ($data) {
                    $message->to($data['email'])->subject($data['title']);  
                });
                $user->remember_token = $random;
                $user->save();

                return response()->json(['success' => true, 'msg' => 'Verification Email Sent Successfully'], 200);
            }else{
                return response()->json(['success' => false, 'msg' => 'User Not Found'], 401);
            }
        }
        else
        {
            return response()->json(['success' => false, 'msg' => 'Unauthorised'], 401);
        }
    }

    //verify email api method call
    public function verifyEmail($token){
        $user = User::where('remember_token',$token)->first();
        if($user){
            $user->email_verified_at = Carbon::now();
            $user->is_verified = 1;
            $user->remember_token = null;
            $user->save();
            return response()->json(['success' => true, 'msg' => 'Email Verified Successfully'], 200);
        }else{
            return response()->json(['success' => false, 'msg' => 'Invalid Token'], 401);
        } 
    }

    
    public function profile()
    {
        if (!auth()->user()) {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
        try {
            return response()->json(['success' => true, 'data' => auth()->user()], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 401);
        }
    }

    public function forgetPassword(Request $request){
        try{

            $user= User::where('email',$request->email)->first();
            if($user){

                $token = Str::random(40);
               
                $datetime = Carbon::now()->format('Y-m-d H:i:s');
               
                $data['url']=URL::to('/').'/api/reset-password/'.$token;
                $data['email']=$user->email;
                $data['title']='Reset Password';
                $data['body']='Please click the below link to reset your password';
                
                Mail::send('mail.resetPassword', ['data'=>$data], function($message) use ($data) {
                    $message->to($data['email'])->subject($data['title']);  
                });

                $passwordReset = PasswordReset::updateOrCreate(
                    ['email' => $user->email],
                    [
                        'email' => $user->email,
                        'token' => $token,
                        'created_at' => $datetime
                    ]
                );

                return response()->json(['success' => true, 'msg' => 'Reset Password Email Sent Successfully'], 200);
            }else{
                return response()->json(['success' => false, 'msg' => 'User Not Found'], 401);
            }
        }
        catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 401);
        }
    }

    public function resetPasswordLoad(Request $request){

        $passwordReset = PasswordReset::where('token', $request->token)->first();
        if (!$passwordReset) {
            //return view 404 page
            return view('404');
        }
        $user = User::where('email',$passwordReset->email)->first();
        $token= $request->token;
        if(!$user){
            return view('401');
        }
       
        return view('resetPassword',compact('user','token'));
    }

    public function resetPassword(Request $request){

        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|string|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $passwordReset = PasswordReset::where('email', $request->email)->where('token', $request->token)->first(); 
        if (!$passwordReset) {
            return response()->json(['success' => false, 'msg' => 'User Not Found'], 401);
        }       
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'msg' => 'User Not Found'], 401);
        }
        $user->password = Hash::make($request->password);
        $user->save();
        $passwordReset->delete();
        return response()->json(['success' => true, 'msg' => 'Password Reset Successfully'], 200);
    }


    protected function responsedWithToken($token)
    {
        return response()->json([
            'success' => true,
            'user' =>  Auth::guard('api')->user(),
            'authorisation' => [
                'token' => $token,
                'type' => 'Bearer',
            ],
            'expires_in' => JWTAuth::factory()->getTTL() ,
           
        ], 200);
    }
}
