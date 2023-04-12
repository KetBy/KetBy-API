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

    public function getProject(Request $request) {
        usleep(50000);
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

    public function updateFile(Request $request) {
        try {
            
            $user = auth()->user();

            $meta = $request->meta?? [];
            $content = $request->content?? [];

            foreach ($content as $instruction) {
                if (!in_array($instruction["gate"], QuantumController::$GATES)) {
                    return response()->json([
                        "success" => false, 
                        "message" => "Undefined gate " . $instruction["gate"]
                    ], 500);
                } else {
                    foreach ($instruction["qubits"] as $qubit) {
                        if (!is_int($qubit) || $qubit < 0 || $qubit >= $meta["qubits"]) {
                            return response()->json([
                                "success" => false, 
                                "message" => "Malformed gate with uid = " . $instruction["uid"] 
                                    . ". Qubit $qubit's gate is invalid."
                            ], 200);
                        }
                    }
                    if (QuantumController::$GATES_DATA[$instruction["gate"]]["qubits"] != count($instruction["qubits"])) {
                        return response()->json([
                            "success" => false, 
                            "message" => "Malformed gate " . $instruction["gate"] 
                                . ". It requires " . QuantumController::$GATES_DATA[$instruction["gate"]]["qubits"] 
                                . " qubits, but " . count($instruction["qubits"]) . " were given"
                        ], 500);
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

            $file = File::where("project_id", "=", $project->id)->where("file_index", "=", $fileIndex)->first();

            if (!$file) {
                return response()->json([
                    "success" => false, 
                    "message" => "This file does not exist.",
                ], 404);
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

        // If the user has update permissions
        if ($this->getPermissions($user, $project) >= 2) {
            // Create a new file
            $file = new File([
                'title' => $title,
                'creator_id' => $user->id,
                'file_index' => $project->next_file_index
            ]);
            $file = $project->files()->save($file);

            $project->next_file_index += 1;
            $project->save();
            
            $project->date = $project->getCreatedAt();

            $files = $project->files()->get();
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

            // If the user has update permissions
            if ($this->getPermissions($user, $project) >= 2) {
                if ($file->delete()) {
                    return response()->json([
                        "success" => true,
                        "status" => "The file has been deleted.",
                        "results" => null
                    ], 200);
                } else {
                    return response()->json([
                        "success" => false,
                        "message" => "Something went wrong. Please try again later. Error code: P_FDEL_0002",
                        "status" => "Could not save changes P_FDEL_0002",
                        "results" => null
                    ], 400);
                }
            } else {
                return response()->json([
                    "success" => false,
                    "status" => "Log in to delete file",
                    "results" => null
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
                    "download_url" => env("APP_URL") . "/project/" . $project->token . "/" . $file->file_index . "/stats.csv?user_id=" . $user->id . "&t=" . $time . "&token=" . hash("sha256", $user->id . $time . env("TOKEN_SALT")) 
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
        if (!$user) {
            return 0;
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
