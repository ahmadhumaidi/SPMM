<?php

namespace App\Http\Requests;

use App\Models\ClassTrack;
use App\Models\StudyProgram;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RegisterLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'campus_id' => ['required', 'exists:campuses,id'],
            'study_program_id' => ['required', 'exists:study_programs,id'],
            'class_track_id' => ['required', 'exists:class_tracks,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'whatsapp_number' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:255'],
            'origin_school' => ['nullable', 'string', 'max:255'],
            'graduation_year' => ['nullable', 'integer', 'min:1970', 'max:'.((int) now()->format('Y') + 1)],
            'source_channel' => ['nullable', 'string', 'max:64'],
            'source_detail' => ['nullable', 'string', 'max:255'],
            'referral_code' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled(['campus_id', 'study_program_id', 'class_track_id'])) {
                    return;
                }

                $studyProgramMatchesCampus = StudyProgram::query()
                    ->whereKey($this->input('study_program_id'))
                    ->where('campus_id', $this->input('campus_id'))
                    ->exists();

                if (! $studyProgramMatchesCampus) {
                    $validator->errors()->add('study_program_id', 'Program studi tidak sesuai dengan kampus yang dipilih.');
                }

                $classTrackMatchesCampus = ClassTrack::query()
                    ->whereKey($this->input('class_track_id'))
                    ->where('campus_id', $this->input('campus_id'))
                    ->exists();

                if (! $classTrackMatchesCampus) {
                    $validator->errors()->add('class_track_id', 'Program perkuliahan tidak sesuai dengan kampus yang dipilih.');
                }
            },
        ];
    }
}
