<?php

namespace App\Http\Controllers;

use App\Enums\RecordingStatus;
use App\Http\Requests\StoreRecordingRequest;
use App\Jobs\TranscribeRecording;
use App\Models\Recording;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RecordingController extends Controller
{
    public function index(): View
    {
        $recordings = Recording::query()
            ->latest()
            ->paginate(10);

        return view('recordings.index', compact('recordings'));
    }

    public function create(): View
    {
        return view('recordings.create');
    }

    public function store(StoreRecordingRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $disk = config('recordings.disk');

        $path = $file->store('recordings', $disk);

        $recording = Recording::create([
            'title' => $request->input('title') ?: $file->getClientOriginalName(),
            'original_filename' => $file->getClientOriginalName(),
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'status' => RecordingStatus::Pending,
        ]);

        TranscribeRecording::dispatch($recording);

        return redirect()
            ->route('recordings.show', $recording)
            ->with('status', 'Upload received — transcription has started.');
    }

    public function show(Recording $recording): View
    {
        return view('recordings.show', compact('recording'));
    }

    /**
     * Lightweight JSON endpoint the detail page polls while a recording is
     * still processing, so the UI updates without a full page reload.
     */
    public function status(Recording $recording): JsonResponse
    {
        return response()->json([
            'status' => $recording->status->value,
            'status_label' => $recording->status->label(),
            'in_progress' => $recording->status->isInProgress(),
            'transcript' => $recording->transcript,
            'summary' => $recording->summary,
            'action_items' => $recording->action_items,
            'error_message' => $recording->error_message,
        ]);
    }

    public function destroy(Recording $recording): RedirectResponse
    {
        Storage::disk($recording->disk)->delete($recording->path);
        $recording->delete();

        return redirect()
            ->route('recordings.index')
            ->with('status', 'Recording deleted.');
    }
}
