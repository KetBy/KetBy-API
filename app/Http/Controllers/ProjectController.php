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

use PHLAK\StrGen;

class ProjectController extends Controller
{
    public function create(Request $request) {
        try {
            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|between:2,100',
                'description' => 'nullable|string|between:2,1000',
            ]);

            if($validator->fails()){
                return response()->json([
                    'success' => false,
                    'field_errors' => $validator->messages()
                ], 400);
            }
            
            // Generate a random project token
            $generator = new StrGen\Generator();
            $token = $generator->charset(StrGen\CharSet::ALPHA_NUMERIC)->length(16)->generate();
            while(Project::where(DB::raw('BINARY `token`'), $token)->count() > 0) {
                $token = $generator->charset(StrGen\CharSet::ALPHA_NUMERIC)->length(16)->generate();
            }

            // Create a new project
            $project = new Project([
                'title' => $request->title,
                'description' => $request->description,
                'token' => $token
            ]);
            $project = $user->projects()->save($project)->refresh();

            // Create a new file
            $file = new File([
                'title' => "New circuit",
                'creator_id' => $user->id,
                'file_index' => $project->next_file_index
            ]);
            $file = $project->files()->save($file);

            $project->next_file_index += 1;
            $project->save();

            $project->files_count = 1;
            $project->date = $project->getCreatedAt();

            return response()->json([
                'success' => true,
                'message' => "Your new project has been created. You are being redirected...",
                'project' => $project,
                'redirect_path' => "/composer/{$project->token}/{$file->file_index}"
            ], 200);
        } catch(Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Something went wrong. Please try again later. Error code: P_CREATE_0001",
                "exception" => $e->message
            ], 400);
        }
    }

    public function getProject(Request $request) {
        try {
            $token = $request->token;
        
            $project = Project::where('token', $token)->first();

            // Access checks 
            // ... todo

            if (!$project) {
                return response()->json([
                    "success" => false, 
                    "message" => "This project does not exist,"
                ], 404);
            }

            $files = $project->files()->get();
            foreach ($files as &$file) {
                $file->meta = $file->getMeta();
                if ($file->meta == NULL) {
                    $file->meta = [
                        "qubits" => 1,
                        "bits" => 0
                    ];
                }
            }

            return response()->json([
                "success" => true,
                "project" => $project,
                "files" => $files
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Something went wrong. Please try again later. Error code: P_GETP_0001",
                "exception" => $e->message
            ], 400);
        }
    }

    /** OUTDATED */
    public function index(Request $request) {
        try {
            $user = auth()->user();

            $projects = $user->projects()->get();

            foreach($projects as &$project) {
                $project->files_count = $project->files()->count();
                $project->date = $project->getCreatedAt();
            }


            return response()->json([
                'projects' => $projects
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
