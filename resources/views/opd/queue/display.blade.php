<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>OPD Queue Display</title>
  <meta http-equiv="refresh" content="30">
  <style>
    body {
      background: #1a1d2e;
      color: #fff;
      font-family: 'Segoe UI', Arial, sans-serif;
      margin: 0;
      padding: 2rem;
    }
    h1 {
      text-align: center;
      margin-bottom: 2rem;
      font-size: 2.5rem;
    }
    .queue-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 1.5rem;
    }
    .queue-card {
      background: #262a41;
      border-radius: 12px;
      padding: 1.5rem;
      border-left: 6px solid #6c757d;
    }
    .queue-card.in_consultation {
      border-left-color: #17c666;
      background: #1e3a2c;
    }
    .queue-card.waiting {
      border-left-color: #ffbb44;
    }
    .token-no {
      font-size: 2rem;
      font-weight: bold;
    }
    .patient-name {
      font-size: 1.1rem;
      opacity: 0.85;
      margin: 0.25rem 0;
    }
    .doctor-name {
      font-size: 1rem;
      opacity: 0.7;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .doctor-name img {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      object-fit: cover;
      flex-shrink: 0;
    }
    .badge {
      display: inline-block;
      padding: 0.25rem 0.75rem;
      border-radius: 999px;
      font-size: 0.8rem;
      margin-top: 0.5rem;
    }
    .badge.waiting { background: #ffbb44; color: #1a1d2e; }
    .badge.in_consultation { background: #17c666; color: #1a1d2e; }
    .empty-state {
      text-align: center;
      opacity: 0.6;
      font-size: 1.3rem;
      margin-top: 4rem;
    }
  </style>
</head>
<body>
  <h1>OPD Queue — {{ now()->format('d M Y') }}</h1>

  @if ($tokens->isEmpty())
  <p class="empty-state">No patients currently in queue.</p>
  @else
  <div class="queue-grid">
    @foreach ($tokens as $t)
    <div class="queue-card {{ $t->status }}">
      <div class="token-no">{{ $t->token_no }}</div>
      <div class="patient-name">{{ $t->patient->name ?? 'N/A' }}</div>
      <div class="doctor-name">
        @if($t->doctor)
        <img src="{{ $t->doctor->photo_url }}" alt="" onerror="this.onerror=null;this.src='{{ asset('images/defaults/doctor.png') }}';" />
        @endif
        {{ $t->doctor->name ?? 'N/A' }}
      </div>
      <span class="badge {{ $t->status }}">{{ $t->status === 'in_consultation' ? 'In Consultation' : 'Waiting' }}</span>
    </div>
    @endforeach
  </div>
  @endif
</body>
</html>
