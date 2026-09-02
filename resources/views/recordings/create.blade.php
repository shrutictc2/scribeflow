@extends('layouts.app')

@section('title', 'Upload a recording · ScribeFlow')

@section('content')
    <h1>Upload a recording</h1>
    <p class="muted">Audio or video, up to {{ number_format(config('recordings.max_upload_kb') / 1024, 0) }} MB. It'll be transcribed and summarized automatically in the background.</p>

    <div class="card">
        <form method="POST" action="{{ route('recordings.store') }}" enctype="multipart/form-data">
            @csrf

            <label for="title">Title (optional)</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" placeholder="e.g. Client kickoff call — 2 Sep">

            <label for="file">Recording file</label>
            <input type="file" id="file" name="file" accept="audio/*,video/*" required>

            <button type="submit">Upload &amp; start processing</button>
            <a href="{{ route('recordings.index') }}" class="btn secondary" style="margin-left:8px">Cancel</a>
        </form>
    </div>
@endsection
