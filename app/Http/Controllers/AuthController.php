<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return object
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json(['message' => 'Invalid email or password.'], 401);
        }

        return $this->createNewToken($token);
    }

    /**
     * Register a User.
     *
     * @param Request $request
     * @return object
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));

        $token = auth()->attempt($validator->validated());
        $status = 201;
        return $this->createNewToken($token, $status);
    }


    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'User successfully signed out'],204);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile($id)
    {
        $user = auth()->user();
        if ($user->id == $id) {
            return response()->json($user);
        } elseif ($user->is_admin) {
            $user = User::findOrFail($id);
            return response()->json($user);
        } else {
            return response()->json(['error' => 'User not found'], 404);
        }
    }

    /**
     * Update User Profile
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserProfile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|between:2,100',
                'email' => 'required|string|email|max:100',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors()->toJson(), 400);
            }
            $user = auth()->user();
            $user->name = $request->get('name');
            $user->email = $request->get('email');
            $user->password = bcrypt($request->get('password'));
            $user->save();

            $token = auth()->refresh();
            return $this->createNewToken($token);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Something went wrong'], 400);
        }
    }
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token, $status = 200)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ], $status);
    }

    /**
     * Get user list for admin only
     *
     * @param  null
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function userList()
    {
        if (Auth::check() && Auth::user()->is_admin) {
            $users = User::all();
            return response()->json($users, 200);
        }
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (Auth::check() && Auth::user()->is_admin) {
            $user = User::findOrFail($id);
            $user->delete();
            return response()->json(['message' => 'User deleted'], 204);
        }
        return response()->json(['message' => 'Unauthorize'], 401);
    }
    /**
     * update user by admin
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function updateUser(Request $request, $id)
    {
        $user = auth()->user();
        if ($user->is_admin) {
            $user = User::findOrFail($id);

            $user->name = $request->get('name');
            $user->email = $request->get('email');
            $user->is_admin = $request->get('is_admin');
            $user->save();
            return response()->json($user, 200);
        }
        return response()->json(['message' => 'Unauthorize'], 401);
    }
}
