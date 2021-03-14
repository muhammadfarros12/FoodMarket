<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\Hash; 
use Illuminate\Support\Facades\Validator; 
use App\Actions\Fortify\PasswordValidationRules;

class UserController extends Controller
{

    use PasswordValidationRules; 

    public function login(Request $request)
    {
        try {
            // melakukan validasi input
            $request->validate([
                'email'=>'email|required',
                'password'=>'required'
            ]);

            // mengecek credentials(login)
            $credentials = request(['email','password']);
            if(!Auth::attempt($credentials)){
                return ResponseFormatter::error([
                    'message' => 'Unauthorized'
                ], 'Authentication Failed', 500);
            }

            // jika hash tidak sesuai maka beri error
            $user = User::where('email', $request->email)->first();
            if(!Hash::check($request->password , $user->password, [])) {
                throw new \Exception('Invalid Credentials');
            }

            // jika berhasil maka loginkan
            $tokenResult = $user->createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'Authenticated', 200);

        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error
            ], 'Authentication Failed', 500);
        }
    }

    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required','string','max:255'],
                'email' => ['required','string','email','max:255','unique:users'],
                'password' => $this->passwordRules()
            ]);

            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'houseNumber' => $request->houseNumber,
                'phoneNumber' => $request->phoneNumber,
                'city' => $request->city,
                'password' => Hash::make($request->password),
            ]);

            $user = User::where('email', $request->email)->first();

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponsFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ]);

        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went Wrong',
                'error' => $error
            ], 'Authentication Failed', 500);
        }
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken()->delete();

        return ResponseFormatter::success($token, 'Token Revoked');
    }

    public function fetch(Request $request)
    {
        return ResponseFormatter::success(
            $request->user(), "Data profile User berhasil diambil");
    }

    public function updateProfile(Request $request)
    {
        $data = $request->all();

        $user = Auth::user();
        $user->update($data);

        return ResponseFormatter::success($user, 'Profile Update');
    }

    public function updatePhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|max:2048'
        ]);

        if($validator->fails())
        {
            return ResponseFormatter::error(
                ['error'=> $validator->errors()],
                'Update photo fails',
                401
            );
        }

        if($request->file('file'))
        {
            $file = $request->file->store('asset/user','public');

            // Simpan foto ke database(urlnya)
            $user = Auth::user();
            $user->profile_photo_path = $file;
            $user->update();

            return ResponseFormatter::success([$file], 'File succesfully uploaded');
        }
    }

}

