<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecordingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $fileRules = ['required', 'file', 'max:'.config('recordings.max_upload_kb')];

        if (! empty(config('recordings.allowed_mime_types'))) {
            $fileRules[] = 'mimetypes:'.implode(',', config('recordings.allowed_mime_types'));
        }

        return [
            'title' => ['nullable', 'string', 'max:255'],
            'file' => $fileRules,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimetypes' => 'That file type isn\'t supported. Upload an audio or video recording (mp3, wav, m4a, mp4, mov, webm, mkv).',
            'file.max' => 'That file is too large. The limit is '.number_format(config('recordings.max_upload_kb') / 1024, 1).' MB.',
        ];
    }
}
