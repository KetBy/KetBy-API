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
use App\Http\Controllers\QuantumController;

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

    public function updateSettings(Request $request) {
        $user = auth()->user();

        try {
            $token = $request->token;
        
            $project = Project::where('token', $token)->first();
            if (!$project) {
                return response()->json([
                    "success" => false, 
                    "message" => "This project does not exist."
                ], 404);
            }

            $permission = $this->getPermissions($user, $project);
            if ($permission < 2) {
                return response()->json([
                    "success" => false,
                    "message" => "You are not allowed to update this project."
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'string|between:2,100',
                'description' => 'string|between:2,1000',
                'public' => 'integer|between:0,1'
            ]);

            if($validator->fails()){
                return response()->json([
                    'success' => false,
                    'field_errors' => $validator->messages()
                ], 400);
            }

            $project->title = $request->title?? $project->title;
            $project->description = $request->description?? $project->description;
            $project->public = $request->public != null? intval($request->public) : $project->public;

            if (!$project->save()) {
                
                return response()->json([
                    "success" => false,
                    "message" => "Something went wrong. Please try again later. Error code: P_PSUPD_0002",
                    
                ], 500);
            }

            $project->author = $project->owner()->first();
            return response()->json([
                "success" => true,
                "message" => "The project settings have been updated.",
                "project" => $project
            ], 200);
            
        } catch(Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Something went wrong. Please try again later. Error code: P_PSUPD_0001",
                "exception" => $e->message
            ], 400);
        }
    }

    public function getProject(Request $request) {
        $user = auth()->user();

        try {
            $token = $request->token;
        
            $project = Project::where('token', $token)->first();

            if (!$project) {
                return response()->json([
                    "success" => false, 
                    "message" => "This project does not exist,"
                ], 404);
            }

            $author = $project->owner()->first();

            $permission = $this->getPermissions($user, $project);
            if ($permission == 0) {
                return response()->json([
                    "success" => false,
                    "message" => "You are not allowed to access this page."
                ], 403);
            }

            $files = $project->files()->orderBy('title')->get();
            foreach ($files as &$file) {
                $file->meta = $file->getMeta();
                $file->content = $file->getContent();
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
                "files" => $files,
                "author" => $author,
                "permissions" => $permission
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Something went wrong. Please try again later. Error code: P_GETP_0001",
                "exception" => $e->message
            ], 400);
        }
    }

    public function updateFileSettings(Request $request) {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|between:2,100',
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'field_errors' => $validator->messages()
            ], 400);
        }

        try {
            $token = $request->token;
            $fileIndex = (int) $request->fileIndex;
        
            $project = Project::where('token', $token)->first();
            if (!$project) {
                return response()->json([
                    "success" => false, 
                    "message" => "This project does not exist."
                ], 404);
            }

            $permission = $this->getPermissions($user, $project);
            if ($permission < 2) {
                return response()->json([
                    "success" => false,
                    "message" => "You are not allowed to update this file."
                ], 403);
            }

            $file = File::where("project_id", "=", $project->id)->where("file_index", "=", $fileIndex)->first();
            if (!$file) {
                return response()->json([
                    "success" => false, 
                    "message" => "This file does not exist.",
                ], 404);
            }

            $title = $request->title;
            $file->title = $title;
            if (!$file->save()) {
                return response()->json([
                    "success" => false,
                    "message" => "Something went wrong. Please try again later. Error code: P_FSUPD_0002",
                ], 500);
            }

            return response()->json([
                "success" => true,
                "message" => "The new file title has been saved.",
            ], 200);

        }  catch(Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Something went wrong. Please try again later. Error code: P_FSUPD_0001",
                "exception" => $e->message
            ], 400);
        }

    }

    public function updateFile(Request $request) {
        $user = auth()->user();

        try {

            $meta = $request->meta? [
                "qubits" => $request->meta["qubits"],
                "bits" => $request->meta["bits"]
            ] : [];
            $content = $request->content?? [];
            $updateCount = $request->count?? 0; // if 0, do not update, just return the file

            foreach ($content as &$instruction) {
                $instruction = [
                    "qubits" => $instruction["qubits"],
                    "params" => $instruction["params"],
                    "gate" => $instruction["gate"],
                    "uid" => $instruction["uid"]?? rand(1, 1000000)
                ];
                if (!in_array($instruction["gate"], QuantumController::$GATES)) {
                    return response()->json([
                        "success" => false, 
                        "message" => "Undefined gate " . $instruction["gate"]
                    ], 500);
                } else {
                    if (QuantumController::$GATES_DATA[$instruction["gate"]]["qubits"] != count($instruction["qubits"])) {
                        return response()->json([
                            "success" => false, 
                            "message" => "Malformed gate " . $instruction["gate"] 
                                . ". It requires " . QuantumController::$GATES_DATA[$instruction["gate"]]["qubits"] 
                                . " qubits, but " . count($instruction["qubits"]) . " were given"
                        ], 500);
                    }
                    if (QuantumController::$GATES_DATA[$instruction["gate"]]["parameters"] != count($instruction["params"])) {
                        return response()->json([
                            "success" => false, 
                            "message" => "Malformed gate " . $instruction["gate"] 
                                . ". It requires " . QuantumController::$GATES_DATA[$instruction["gate"]]["parameters"] 
                                . " parameters, but " . count($instruction["params"]) . " were given"
                        ], 500);
                    }
                    foreach ($instruction["qubits"] as $qubit) {
                        if (!is_int($qubit) || $qubit < 0 || $qubit >= $meta["qubits"]) {
                            return response()->json([
                                "success" => false, 
                                "message" => "Malformed gate with uid = " . $instruction["uid"] 
                                    . ". `$qubit` is not a valid qubit."
                            ], 500);
                        }
                    }
                    foreach ($instruction["params"] as $parameter) {
                        if (!QuantumController::validateSimpleFraction($parameter)) {
                            return response()->json([
                                "success" => false, 
                                "message" => "Malformed gate with uid = " . $instruction["uid"] 
                                    . ". Parameter value `$parameter` is invalid."
                            ], 500);
                        }
                    }
                }
            }

            $token = $request->token;
            $fileIndex = (int) $request->fileIndex;
        
            $project = Project::where('token', $token)->first();

            if (!$project) {
                return response()->json([
                    "success" => false, 
                    "message" => "This project does not exist."
                ], 404);
            }

            $permission = $this->getPermissions($user, $project);
            if ($permission < 2) {
                return response()->json([
                    "success" => false,
                    "message" => "You are not allowed to update this file."
                ], 403);
            }

            $file = File::where("project_id", "=", $project->id)->where("file_index", "=", $fileIndex)->first();

            if (!$file) {
                return response()->json([
                    "success" => false, 
                    "message" => "This file does not exist.",
                ], 404);
            }

            if ($updateCount == 0) {
                // If the user has read permissions
                if ($this->getPermissions($user, $project) >= 1) {
                    $file->meta = json_encode($meta);
                    $file->content = json_encode($content);
                    return response()->json([
                        "success" => true,
                        "status" => "All changes saved",
                        "file" => [
                            "meta" => $file->getMeta(),
                            "content" => $file->getContent()
                        ]
                    ], 200);
                }
            }

            // If the user has update permissions
            if ($this->getPermissions($user, $project) >= 2) {
                $file->meta = json_encode($meta);
                $file->content = json_encode($content);
                if ($file->save()) {
                    return response()->json([
                        "success" => true,
                        "status" => "All changes saved",
                        // "results" => $this->runFile($file)
                        "file" => [
                            "meta" => $file->getMeta(),
                            "content" => $file->getContent()
                        ]
                    ], 200);
                } else {
                    return response()->json([
                        "success" => false,
                        "message" => "Something went wrong. Please try again later. Error code: P_FUPD_0002",
                        "status" => "Could not save changes P_FUPD_0002",
                        "results" => null
                    ], 400);
                }
            } else {
                return response()->json([
                    "success" => false,
                    "status" => "Log in to save changes",
                    "results" => null
                ], 400);
            }
            
        } catch(Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Something went wrong. Please try again later. Error code: P_FUPD_0001",
                "exception" => $e->message
            ], 400);
        }
    }

    public function createFile(Request $request) {
        $user = auth()->user();

        $token = $request->token;
        $title = $request->title?? "Unnamed circuit";

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|between:2,100',
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'field_errors' => $validator->messages()
            ], 400);
        }

        $project = Project::where('token', $token)->first();
        if (!$project) {
            return response()->json([
                "success" => false, 
                "message" => "This project does not exist."
            ], 404);
        }

        $permission = $this->getPermissions($user, $project);
        if ($permission < 2) {
            return response()->json([
                "success" => false,
                "message" => "You are not allowed to create a new file for this project."
            ], 403);
        }

        // If the user has update permissions
        if ($this->getPermissions($user, $project) >= 2) {
            // Create a new file
            $file = new File([
                'title' => $title,
                'creator_id' => $user->id,
                'file_index' => $project->next_file_index,
            ]);
            $file = $project->files()->save($file);

            $project->next_file_index += 1;
            $project->save();
            
            $project->date = $project->getCreatedAt();

            $files = $project->files()->get();
            foreach ($files as &$file) {
                $file->meta = $file->getMeta();
                $file->content = $file->getContent();
            }
            $project->files_count = count($files);

            return response()->json([
                "success" => true,
                "project" => $project,
                "files" => $files,
                "file" => $file
            ], 200);
            
        } else {
            return response()->json([
                "success" => false,
                "status" => "Log in to create new files",
                "results" => null
            ], 400);
        }
    }

    public function deleteFile(Request $request) {
        try {
            
            $user = auth()->user();

            $token = $request->token;
            $fileIndex = (int) $request->fileIndex;
        
            $project = Project::where('token', $token)->first();

            if (!$project) {
                return response()->json([
                    "success" => false, 
                    "message" => "This project does not exist."
                ], 404);
            }

            $file = File::where("project_id", "=", $project->id)->where("file_index", "=", $fileIndex)->first();

            if (!$file) {
                return response()->json([
                    "success" => false, 
                    "message" => "This file does not exist.",
                ], 404);
            }

            $permission = $this->getPermissions($user, $project);
            if ($permission < 2) {
                return response()->json([
                    "success" => false,
                    "message" => "You are not allowed to delete this file."
                ], 403);
            }

            // If the user has update permissions
            if ($this->getPermissions($user, $project) >= 2) {
                // If the project does not have at least 2 files
                if ($project->files()->count() < 2) {
                    return response()->json([
                        "success" => false,
                        "message" => "You cannot delete the file because it is the only file of this project. Please create a new file and try deleting again. Error code: P_FDEL_0003",
                    ], 400);
                }
                if ($file->delete()) {
                    return response()->json([
                        "success" => true,
                        "message" => "The file has been deleted.",
                    ], 200);
                } else {
                    return response()->json([
                        "success" => false,
                        "message" => "Something went wrong. Please try again later. Error code: P_FDEL_0002",
                    ], 400);
                }
            } else {
                return response()->json([
                    "success" => false,
                    "message" => "Log in to delete file",
                ], 400);
            }
            
        } catch(Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Something went wrong. Please try again later. Error code: P_FDEL_0001",
                "exception" => $e->message
            ], 400);
        }
    }

    public function getStats(Request $request) {
        try {
            
            $user = auth()->user();

            $token = $request->token;
            $fileIndex = (int) $request->fileIndex;
        
            $project = Project::where('token', $token)->first();

            if (!$project) {
                return response()->json([
                    "success" => false, 
                    "message" => "This project does not exist."
                ], 404);
            }

            $file = File::where("project_id", "=", $project->id)->where("file_index", "=", $fileIndex)->first();

            if (!$file) {
                return response()->json([
                    "success" => false, 
                    "message" => "This file does not exist.",
                ], 404);
            }

            $permission = $this->getPermissions($user, $project);
            if ($permission == 0) {
                return response()->json([
                    "success" => false,
                    "message" => "You are not allowed to access the statistics for this project."
                ], 403);
            }

            $time = time();

            // If the user has read permissions
            if ($this->getPermissions($user, $project) >= 1) {
                // Check if the circuit only has <= 5 qubits
                if ($file->getMeta()->qubits > 5) {
                    if ($file->getMeta()->qubits <= 10) {
                        return response()->json([
                            "success" => false,
                            "message" => "Outcome probabilities are shown for circuits with up to 5 qubits. For circuits with less than 10 qubits, you can still download the probabilities as a CSV file.",
                            "download_url" => env("APP_URL") . "/project/" . $project->token . "/" . $file->file_index . "/stats.csv?user_id=" . $user->id . "&t=" . $time . "&token=" . hash("sha256", $user->id . $time . env("TOKEN_SALT")) 
                        ], 200);
                    } else {
                        return response()->json([
                            "success" => false,
                            "message" => "Outcome probabilities can only be computed for circuits with up to 10 qubits.",
                            "download_url" => NULL
                        ], 200);
                    }
                }
                return response()->json([
                    "success" => true,
                    "results" => $this->_getStats($file),
                    "download_url" => env("APP_URL") . "/project/" . $project->token . "/" . $file->file_index . "/stats.csv?user_id=" . ($user? $user->id : -1) . "&t=" . $time . "&token=" . hash("sha256", ($user? $user->id : -1) . $time . env("TOKEN_SALT")) 
                ], 200);
            } else {
                return response()->json([
                    "success" => false,
                    "status" => "Log in to access this",
                    "results" => null
                ], 400);
            }
            
        } catch(Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Something went wrong. Please try again later. Error code: P_FSTATS_0001",
                "exception" => $e->message
            ], 400);
        }
    }

    /**
     * Get an user's permissions for a project.
     * 0 - no access
     * 1 - read access
     * 2 - update access
     * 
     * @param Class::User $user
     * @param Class::Project $project
     * @return integer $permission
     */
    protected function getPermissions($user, $project) {
        if (!$user || $user->id != $project->owner_id) {
            if ($project->public == 1) {
                return 1;
            } else {
                return 0;
            }
        }
        if ($project->owner_id == $user->id) {
            return 2;
        }
        return 0;
    }

    /**
     * Run a file's instructions in Qiskit.
     * 
     * @param Class::File $file
     * @return Object
     */
    protected function _getStats($file) {
        return QuantumController::getInfo($file->getMeta()->qubits, $file->getContent());
    }
}
