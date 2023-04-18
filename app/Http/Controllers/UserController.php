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
}
