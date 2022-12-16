<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Registration;
use Validator;
use App\Mail\Send;
use Illuminate\Support\Facades\Mail;

use PHLAK\StrGen;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request){
    	$validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        if (! $token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $this->createNewToken($token);
    }
    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|between:2,100',
            'last_name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'field_errors' => $validator->messages()
            ], 400);
        }

        // Prevent spam
        if (Registration::where('email', '=', $request->email)->count() >= 5) {
            return response()->json([
                'success' => false,
                'message' => "You have tried too many times. Please try again later or contact us if the problem persists. Error code: R001",
            ], 400);
        }

        // Generate a random confirmation token
        $generator = new StrGen\Generator();
        $token = $generator->charset(StrGen\CharSet::ALPHA_NUMERIC)->length(32)->generate();
        while(Registration::where('token', '=', $token)->count() > 0) {
            $token = $generator->charset(StrGen\CharSet::ALPHA_NUMERIC)->length(32)->generate();
        }

        $registration = Registration::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password), 'token' => $token]
        ));

        if(!$registration) {
            return response()->json([
                'success' => false,
                'message' => "Something went wrong. Please try again later. Error code: R002",
                'registration' => $registration
            ], 400);
        }

        // Send confirmation email
        $mail = Mail::to($request->email)->send(
            new Send("Confirm your KetBy account", "mail.confirm", ['token' => $token])
        );

        if(!$mail) {
            return response()->json([
                'success' => false,
                'message' => "Something went wrong. Please try again later. Error code: R003",
                'registration' => $registration
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => "We've sent you an email. Please check it in order to activate your account.",
            'registration' => $registration
        ], 200);

    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        auth()->logout();
        return response()->json([
            'success' => true,
            'message' => "You've logged out of your account successfully. See you later!"
        ], Response::HTTP_OK);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {
        return $this->createNewToken(auth()->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function status() {
        $user = auth()->user();
        if($user) {
            return response()->json([
                'is_logged_in' => true,
                'user' => $user
            ], 200);
        } else {
            return response()->json([
                'is_loggedn_in' => false,
                'user' => $user
            ], 401);
        }
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }
}