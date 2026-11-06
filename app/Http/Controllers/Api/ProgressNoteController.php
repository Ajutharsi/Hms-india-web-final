<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesRoles;
use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\ProgressNote;
use App\Models\Vital;
use Illuminate\Http\Request;

/**
 * The ward round.
 *
 * Nurses had nursing notes and the clinic had consultations, but the
 * daily round on an admitted patient -- how yesterday's plan went and
 * what today's is -- had nowhere to live.
 *
 * The list is ordered by who has waited longest for a note, because
 * that is the question on a round: who have I not seen today.
 */
class ProgressNoteController extends Controller
{
    use AuthorizesRoles;

    private function authClinical(Request $request)
    {
        return $this->authRoles($request, ['doctor', 'nurse'], 'This account cannot write round notes.');
    }

    public function index(Request $request)
    {
        $this->authClinical($request);

        $admissions = Admission::with(['patient:id,name,mrn', 'ward:id,name', 'bed:id,bed_no', 'doctor:id,name'])
            ->where('status', 'admitted')
            ->when($request->ward_id, fn ($q, $id) => $q->where('ward_id', $id))
            ->get();

        $latest = ProgressNote::selectRaw('admission_id, MAX(noted_at) as last_note')
            ->whereIn('admission_id', $admissions->pluck('id'))
            ->groupBy('admission_id')
            ->pluck('last_note', 'admission_id');

        $rows = $admissions->map(function (Admission $a) use ($latest) {
            $last = $latest[$a->id] ?? null;

            return [
                'admission_id' => $a->id,
                'admission_no' => $a->admission_no,
                'patient_id' => $a->patient_id,
                'patient_name' => $a->patient?->name,
                'patient_mrn' => $a->patient?->mrn,
                'ward_name' => $a->ward?->name,
                'bed_no' => $a->bed?->bed_no,
                'doctor_name' => $a->doctor?->name,
                'last_note_at' => $last ? \Illuminate\Support\Carbon::parse($last)->format('Y-m-d H:i') : null,
                // What the round is really asking.
                'seen_today' => $last !== null && \Illuminate\Support\Carbon::parse($last)->isToday(),
            ];
        })
        // Longest unseen first; anyone seen today sinks to the bottom.
        ->sortBy(fn ($row) => $row['last_note_at'] ?? '')
        ->values();

        return response()->json([
            'stats' => [
                'admitted' => $rows->count(),
                'seen_today' => $rows->where('seen_today', true)->count(),
                'not_seen' => $rows->where('seen_today', false)->count(),
            ],
            'patients' => $rows->all(),
        ]);
    }

    public function show(Request $request, Admission $admission)
    {
        $this->authClinical($request);

        $admission->load(['patient:id,name,mrn', 'ward:id,name', 'bed:id,bed_no']);

        $notes = ProgressNote::with('author:id,name')
            ->where('admission_id', $admission->id)
            ->latest('noted_at')
            ->limit(100)
            ->get();

        // Shown beside the note being written: the round's numbers.
        $vitals = Vital::where('patient_id', $admission->patient_id)
            ->latest('recorded_at')
            ->first();

        return response()->json([
            'admission' => [
                'id' => $admission->id,
                'admission_no' => $admission->admission_no,
                'patient_id' => $admission->patient_id,
                'patient_name' => $admission->patient?->name,
                'patient_mrn' => $admission->patient?->mrn,
                'ward_name' => $admission->ward?->name,
                'bed_no' => $admission->bed?->bed_no,
                'admission_reason' => $admission->admission_reason,
            ],
            'last_vitals' => $vitals === null ? null : [
                'blood_pressure' => $vitals->blood_pressure,
                'temperature' => $vitals->temperature,
                'pulse_rate' => $vitals->pulse_rate,
                'spo2' => $vitals->spo2,
                'recorded_at' => optional($vitals->recorded_at)->format('Y-m-d H:i'),
            ],
            'notes' => $notes->map(fn (ProgressNote $n) => [
                'id' => $n->id,
                'note_type' => $n->note_type,
                'subjective' => $n->subjective,
                'objective' => $n->objective,
                'assessment' => $n->assessment,
                'plan' => $n->plan,
                'author' => $n->author?->name,
                'noted_at' => optional($n->noted_at)->format('Y-m-d H:i'),
            ])->all(),
        ]);
    }

    public function store(Request $request, Admission $admission)
    {
        $user = $this->authClinical($request);

        $validated = $request->validate([
            'note_type' => ['nullable', 'in:round,progress,handover'],
            'subjective' => ['nullable', 'string', 'max:2000'],
            'objective' => ['nullable', 'string', 'max:2000'],
            'assessment' => ['nullable', 'string', 'max:2000'],
            'plan' => ['nullable', 'string', 'max:2000'],
        ]);

        // A note with none of the four fields filled records nothing.
        $written = collect(['subjective', 'objective', 'assessment', 'plan'])
            ->filter(fn ($k) => filled($validated[$k] ?? null));

        abort_if($written->isEmpty(), 422, 'Write at least one part of the note.');

        $note = ProgressNote::create($validated + [
            'admission_id' => $admission->id,
            'patient_id' => $admission->patient_id,
            'author_id' => $user->id,
            'note_type' => $validated['note_type'] ?? 'round',
            'noted_at' => now(),
        ]);

        return response()->json(['message' => 'Note added.', 'id' => $note->id], 201);
    }

    public function destroy(Request $request, ProgressNote $note)
    {
        $user = $this->authClinical($request);

        abort_unless(
            $note->author_id === $user->id || $user->hasAnyRole(['admin', 'super_admin']),
            403,
            'That note was written by somebody else.'
        );

        $note->delete();

        return response()->json(['message' => 'Note removed.']);
    }
}
