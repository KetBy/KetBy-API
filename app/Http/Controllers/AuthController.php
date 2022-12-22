<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Registration;
use Validator;
use App\Jobs\SendEmailJob;
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
        // $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request){
    	$validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, "message" => "", "field_errors" => $validator->errors()], 400);
        }
        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json(['success' => false, "message" => "Wrong credentials. Please try again."], 401);
        }
        return response()->json(['success' => true, 'token' => $this->createNewToken($token)]);
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

        $firstName = ucwords(strtolower($request->first_name));
        $lastName = ucwords(strtolower($request->last_name));

        // Generate a random confirmation token
        $generator = new StrGen\Generator();
        $token = $generator->charset(StrGen\CharSet::ALPHA_NUMERIC)->length(32)->generate();
        while(Registration::where(DB::raw('BINARY `token`'), $token)->count() > 0) {
            $token = $generator->charset(StrGen\CharSet::ALPHA_NUMERIC)->length(32)->generate();
        }

        $registration = Registration::create([
            'password' => bcrypt($request->password), 
            'token' => $token,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $request->email
        ]);

        if(!$registration) {
            return response()->json([
                'success' => false,
                'message' => "Something went wrong. Please try again later. Error code: R002",
            ], 400);
        }

        // Send confirmation email
        dispatch(new SendEmailJob(
            $request->email, 
            "Activate your KetBy account", 
            "mail.confirm", 
            ['token' => $token]
        ));

        return response()->json([
            'success' => true,
            'message' => "You'll soon get an email from us. Please check it in order to activate your account.",
            'registration' => $registration
        ], 200);

    }

    /**
     * Confirm a registration.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirm(Request $request) {

        $data = $request->only('token');
        
        // Validate input
        $validator = Validator::make($data, [
            'token' => 'required|string',
        ]);

        $token = $request->token;

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "This link does not exist or is broken. Please try registering again. Error code: C0001"
            ], 200);
        }

        // Check if registration exists
        $registration = Registration::where(DB::raw('BINARY `token`'), $token)->first();

        // If the token is invalid
        if(!$registration) {
            return response()->json([
                'success' => false,
                'message' => "This link does not exist or is broken. Please try registering again. Error code: C0002"
            ], 200);
        }

        // Delete all registrations with the same email
        Registration::where('email', $registration->email)->delete();

        // Generate unique username
        $generator = new StrGen\Generator();
        $username = "user_" . $generator->charset(StrGen\CharSet::NUMERIC)->length(12)->generate();
        while(User::where('username', '=', $token)->count() > 0) {
            $username = "user_" . $generator->charset(StrGen\CharSet::NUMERIC)->length(12)->generate();
        }

        // If the deletion is successful, create a new account
        $user = User::create([
            'first_name' => $registration->first_name,
            'last_name' => $registration->last_name,
            'email' => $registration->email,
            'password' => $registration->password,
            'username' => $username
        ]);

        // If user creating failed
        if(!$user) {
            return response()->json([
                'success' => false,
                'message' => "This link does not exist or is broken. Please try registering again. Error code: C0003"
            ], 200);
        } else {

            // Send welcome email
            dispatch(new SendEmailJob(
                $registration->email, 
                "Welcome to KetBy", 
                "mail.welcome", 
                ['first_name' => $registration->first_name]
            ));

            return response()->json([
                'success' => true,
                'message' => "Your account has been confirmed. You can now log in."
            ], 200);

        }

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
        ], 200);
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
                'is_logged_in' => false,
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