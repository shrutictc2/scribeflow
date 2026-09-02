@extends('layouts.app')

@section('title', 'Recordings · ScribeFlow')

@section('content')
    <div class="top-row">
        <h1>Recordings</h1>
        <a href="{{ route('recordings.create') }}" class="btn">Upload a recording</a>
    </div>

    <div class="card">
        @if ($recordings->isEmpty())
            <p class="muted">No recordings yet. Upload an audio or video file to get started.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Uploaded</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recordings as $recording)
                        <tr onclick="window.location='{{ route('recordings.show', $recording) }}'" style="cursor:pointer">
                            <td>{{ $recording->title }}</td>
                            <td><span class="{{ $recording->status->badgeClass() }}">{{ $recording->status->label() }}</span></td>
                            <td class="muted">{{ $recording->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top:16px">
                {{ $recordings->links() }}
            </div>
        @endif
    </div>
@endsection
