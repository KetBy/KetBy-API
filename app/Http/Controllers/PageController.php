<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;

class PageController extends Controller
{
    public function index(Request $request) {
        $highlightedProjects = Project::where('highlighted', '=', 1)->take(4)->get();
        foreach($highlightedProjects as &$project) {
            $files = $project->files;
            $project->files_count = $files->count();
            $project->date = $project->getCreatedAt();
            $project->first_file_index = min(array_column($files->all(), "file_index"));
            $project->author = $project->owner()->first();
            unset($project->files);
        }

        return response()->json([
            "success" => true,
            "highlighted" => $highlightedProjects
        ], 200);
    }
}
