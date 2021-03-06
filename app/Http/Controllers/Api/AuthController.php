<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function signup(Request $request){
        $request->validate([
            'name' => 'required',
            'email' => 'required|string|unique:users',
            'password' => 'required|string',
            's_phone' => 'required|string|min:7|max:10',

        ]);
        $user =  User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            's_phone' => $request->s_phone,

        ]);


        return response()->json(
            [
                'status'=>[
                    'success'=>true,
                    'code'=> 1,
                    'message'=>'Successfully created user!'
                ],
                'user'=>$user]);

    }

    public function login(Request $request){
//
//        DB::delete('delete from oauth_clients');
//        Artisan::call('passport:client', array( '--personal' => true));
//
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);
        $credentials = request(['email', 'password']);
        if(!Auth::attempt($credentials))
            return response()->json([
                'message' => 'User Not Found'
            ], 422);
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $accessToken = $tokenResult->accessToken;
//        dd($tokenResult->token->expires_at);
//        $token = $tokenResult->token;

        if ($request->remember_me)
            $tokenResult->token->expires_at = Carbon::now()->addWeeks(1);
        $tokenResult->token->save();
        return response()->json([
            'status'=>[
                'success'=>true,
                'code'=> 1,
                'message'=>'success login'
            ],
            'data' => $user,
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString(),
        ]);
    }

    public function logout(Request $request){
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function userProfile(Request $request){
        return response()->json(
            [
                'status'=>[
                    'success'=>true,
                    'code'=> 1,
                    'message'=>'User Profile'
                ],
                'user'=>$request->user()]);

    }


    public function updateProfile(Request $request,User $user){
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required',
                's_phone' => 'nullable|string|min:7|max:10',
                's_image' => 'nullable|image',
                's_address' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                $error = $validator->errors()->all()[0];
                return response()->json(['status' => 'false',
                    'message' => $error,
                    'data' => []], 422);
            } else {

                $user = User::find(Auth::user()->id);

                $user->name = $request->name;
                $user->email = $request->email;
                $user->s_phone = $request->s_phone;
                $user->s_address = $request->s_address;


                if($request->hasfile('s_image')) {
                    $request->file('s_image')->move(public_path('img/products/'), $request->file('s_image')->getClientOriginalName());
                    $user['s_image'] = 'https://newlinetech.site/jourystore/public/img/products/' . $request->file('s_image')->getClientOriginalName();
                }


                $user->save();

                return response()->json(
                    [
                        'status'=>[
                            'success'=>true,
                            'code'=> 1,
                            'message'=>'profile updated !'
                        ],
                        'user'=>$user]);

      }
        }catch (\Exception $exception){
            return response()->json(
                [

                    'status'=>[
                        'success'=>false,
                        'code'=> 0,
                        'message'=>$exception->getMessage()
                    ],
                    'user'=>[]]);


        }




    }





}
