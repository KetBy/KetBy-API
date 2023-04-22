<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Registration;
use App\Models\Project;
use App\Models\File;
use Validator;
use Aws\S3\S3Client;
use PHLAK\StrGen;

class UserController extends Controller
{

    public function index(Request $request) {
        try {
            $username = $request->username;
            
            $user = User::where('username', $username)->first();

            if (!$user) {
                return response()->json([
                    "success" => false, 
                    "message" => "This user does not exist,"
                ], 404);
            } else {
                $user->public_projects_count = $user->projects()->where('public', 1)->count();
                $user->join_date  = $user->getCreatedAt();
                return response()->json([
                    "success" => true,
                    "user" => $user
                ]);
            }
            
        } catch(Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Something went wrong. Please try again later. Error code: U_INDEX_0001",
                "exception" => $e->message
            ], 400);
        }
        
    }

    public function updateAvatar(Request $request) {

        try {
            $username = $request->username;
            
            $user = User::where('username', $username)->first();

            if (!$user) {
                return response()->json([
                    "success" => false, 
                    "message" => "This user does not exist."
                ], 404);
            }

            $loggedInUser = auth()->user();
            if ($loggedInUser->id != $user->id) {
                return response()->json([
                    "success" => false, 
                    "message" => "You are not allowed to access this resource."
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'token' => 'required|regex:/^[a-zA-Z0-9]{16}$/',
                'color_1' => 'required|regex:/^[a-fA-F0-9]{6}$/',
                'color_2' => 'required|regex:/^[a-fA-F0-9]{6}$/',
            ]);

            if($validator->fails()){
                return response()->json([
                    'success' => false,
                    'message' => "Wrong parameters. Please try again later."
                ], 400);
            }

            $token = $request->token;
            $color1 = $request->color_1;
            $color2 = $request->color_2;

            $generator = new StrGen\Generator();
            $fileName = $generator->charset(StrGen\CharSet::ALPHA_NUMERIC)->length(32)->generate() . ".svg";


            $url = "https://api.dicebear.com/6.x/avataaars/svg?seed=$token&backgroundColor=$color1,$color2&backgroundType=gradientLinear";

            $data = file_get_contents($url);
            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => "Something went wrong. Please try again later. Error code: AVTR0002"
                ], 400);
            }

            $client = new S3Client([
                'version' => 'latest',
                'region' => env("CDN_REGION"),
                'endpoint' => env("CDN_ENDPOINT"),
                'credentials' => [
                    'key' => env("CDN_KEY"),
                    'secret' => env("CDN_SECRET"),
                ],
            ]);

            $bucket = env("CDN_BUCKET");
            $key =  "users/avatars/" . (env("APP_ENV") == "local"? "dev_" : "") . $fileName;

            $result = $client->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'Body' => $data,
                'ContentType' => 'image/svg+xml',
                'ACL' => 'public-read',
            ]);

            if ($result['@metadata']['statusCode'] === 200) {
                // Delete the old avatar
                $deletedKey = str_replace(env("CDN_URL") . "/", "", $user->avatar_url);
                if ($user->avatar_url != '/users/default_avatar.svg') {
                    $client->deleteObject([
                        'Bucket' => $bucket,
                        'Key' => $deletedKey
                    ]);
                }
                $user->avatar_url = "/$key";
                $user->save();

                return response()->json([
                    "success" => true,
                    "user" => $user,
                    "_meta" => $deletedKey
                ], 200);
               
            } else {
                return response()->json([
                    "success" => false,
                    "message" => "Something went wrong. Please try again later. Error code: AVTR0003",
                ], 400);
            }

        } catch(Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Something went wrong. Please try again later. Error code: AVTR0001",
                "exception" => $e->message
            ], 400);
        }
    }

    public function getProjects(Request $request) {
        try {
            $username = $request->username;
            
            $user = User::where('username', $username)->first();

            if (!$user) {
                return response()->json([
                    "success" => false, 
                    "message" => "This user does not exist,"
                ], 404);
            }

            $loggedInUser = auth()->user();

            if ($loggedInUser && $loggedInUser->id == $user->id) {
                $projects = $user->projects()->get();
            } else {
                $projects = $user->projects()->where('public', 1)->get();
            }

            foreach($projects as &$project) {
                $files = $project->files;
                $project->files_count = $files->count();
                $project->date = $project->getCreatedAt();
                $project->first_file_index = min(array_column($files->all(), "file_index"));
                $project->author = $loggedInUser;
                unset($project->files);
            }

            return response()->json([
                "success" => true,
                "projects" => $projects
            ], 200);
            
        } catch(Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Something went wrong. Please try again later. Error code: P_INDEX_0001",
                "exception" => $e->message
            ], 400);
        }
        
    }

    public function updateSettings(Request $request) {
        try {
            $username = $request->username;
            
            $user = User::where('username', $username)->first();

            if (!$user) {
                return response()->json([
                    "success" => false, 
                    "message" => "This user does not exist."
                ], 404);
            }

            $loggedInUser = auth()->user();
            if ($loggedInUser->id != $user->id) {
                return response()->json([
                    "success" => false, 
                    "message" => "You are not allowed to access this resource."
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'first_name' => 'required|regex:/^[a-zA-Z-\s]+$/|between:2,32',
                'last_name' => 'required|regex:/^[a-zA-Z-\s]+$/|between:2,32',
                'new_username' => 'required|alpha_num|between:4,24'
            ]);

            if($validator->fails()){
                return response()->json([
                    'success' => false,
                    'field_errors' => $validator->messages()
                ], 400);
            }

            // Check whether the username is already being in use
            if ($user->username != $request->new_username) {
                if (User::where('username', '=', $request->new_username)->count() > 0) {
                    return response()->json([
                        'success' => false,
                        'field_errors' => [
                            "new_username" => "This username is already used by someone else."
                        ]
                    ], 400);
                }
            }

            $user->first_name = ucwords(strtolower($request->first_name));
            $user->last_name = ucwords(strtolower($request->last_name));
            $user->username = $request->new_username;

            if ($user->save()) {
                return response()->json([
                    "success" => true,
                    "user" => $user
                ], 200);
            } else {
                return response()->json([
                    "success" => false,
                    "message" => "Something went wrong. Please try again later. Error code: P_UPDST_0002",
                ], 400);
            }
            
        } catch(Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Something went wrong. Please try again later. Error code: P_UPDST_0001",
                "exception" => $e->message
            ], 400);
        }
    }
}
