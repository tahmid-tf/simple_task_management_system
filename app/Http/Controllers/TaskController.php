<?php
namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        return view('tasks.index');
    }

    public function getTasks()
    {
        if (request()->ajax()) {
            return response()->json([
                'data' => Task::latest()->get(),
            ]);
        } else {
            return redirect()->route('tasks.index');
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required',
            'description' => 'required',
        ]);

        $task = Task::create($request->all());

        return response()->json(['success' => true, 'task' => $task]);
    }

    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        $task->update($request->only(['title', 'description', 'status']));

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        Task::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
