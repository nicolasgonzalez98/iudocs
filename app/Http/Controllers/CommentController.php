<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Comentar un material. Cualquier usuario activo puede.
     */
    public function store(Request $request, Material $material): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $material->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return back();
    }

    /**
     * Borrar un comentario: solo el autor o un admin.
     */
    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isAdmin() && $comment->user_id !== $user->id) {
            abort(403);
        }

        $comment->delete();

        return back();
    }
}
