@extends('layouts.app')

@section('title', $recording->title.' · ScribeFlow')

@section('content')
    <div class="top-row">
        <div>
            <h1 style="margin-bottom:4px">{{ $recording->title }}</h1>
            <span class="muted">{{ $recording->original_filename }} &middot; uploaded {{ $recording->created_at->diffForHumans() }}</span>
        </div>
        <span id="status-badge" class="{{ $recording->status->badgeClass() }}">{{ $recording->status->label() }}</span>
    </div>

    @if ($recording->status === \App\Enums\RecordingStatus::Failed)
        <div class="error-list">
            <strong>Processing failed:</strong> {{ $recording->error_message }}
        </div>
    @endif

    <div class="card" id="summary-card" @if(!$recording->summary) style="display:none" @endif>
        <h2>Summary</h2>
        <p id="summary-text">{{ $recording->summary }}</p>

        <h2 style="margin-top:18px">Action items</h2>
        <ul class="action-items" id="action-items-list">
            @foreach ($recording->action_items ?? [] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </div>

    <div class="card" id="progress-card" @if($recording->summary) style="display:none" @endif>
        <h2>Processing</h2>
        <p class="muted" id="progress-text">
            @if ($recording->status->isInProgress())
                This page updates automatically — no need to refresh.
            @else
                This recording did not complete successfully.
            @endif
        </p>
    </div>

    <div class="card" id="transcript-card" @if(!$recording->transcript) style="display:none" @endif>
        <h2>Transcript</h2>
        <div class="transcript-box" id="transcript-text">{{ $recording->transcript }}</div>
    </div>

    <form method="POST" action="{{ route('recordings.destroy', $recording) }}" onsubmit="return confirm('Delete this recording and its file?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="danger">Delete recording</button>
    </form>

    @if ($recording->status->isInProgress())
        <script>
            (function poll() {
                fetch("{{ route('recordings.status', $recording) }}")
                    .then((r) => r.json())
                    .then((data) => {
                        document.getElementById('status-badge').textContent = data.status_label;

                        if (data.transcript) {
                            document.getElementById('transcript-card').style.display = '';
                            document.getElementById('transcript-text').textContent = data.transcript;
                        }

                        if (data.summary) {
                            document.getElementById('summary-card').style.display = '';
                            document.getElementById('progress-card').style.display = 'none';
                            document.getElementById('summary-text').textContent = data.summary;
                            const list = document.getElementById('action-items-list');
                            list.innerHTML = '';
                            (data.action_items || []).forEach((item) => {
                                const li = document.createElement('li');
                                li.textContent = item;
                                list.appendChild(li);
                            });
                        }

                        if (data.error_message) {
                            document.getElementById('progress-text').textContent = 'Failed: ' + data.error_message;
                        }

                        if (data.in_progress) {
                            setTimeout(poll, 2500);
                        } else if (!data.summary) {
                            location.reload();
                        }
                    })
                    .catch(() => setTimeout(poll, 5000));
            })();
        </script>
    @endif
@endsection
