<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $v = $request->validate([
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'name'     => 'nullable|string|max:50',
        ]);

        $user = User::create([
            'name'     => $v['name'] ?? 'user',
            'email'    => $v['email'],
            'password' => Hash::make($v['password']),
        ]);

        $token = $user->createToken('unity')->plainTextToken;

        return response()->json(['token'=>$token,'user'=>[
            'id'=>$user->id,'name'=>$user->name,'email'=>$user->email
        ]], 201);
    }

public function login(Request $request)
{
    $v = $request->validate([
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

    $email = strtolower(trim($v['email']));
    $user = \App\Models\User::whereRaw('LOWER(email) = ?', [$email])->first();

    if (!$user || !\Illuminate\Support\Facades\Hash::check($v['password'], $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $token = $user->createToken('unity')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user'  => ['id'=>$user->id,'name'=>$user->name,'email'=>$user->email],
    ]);
}
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message'=>'Logged out']);
    }
}
