{{--
    Shared field set for the Add/Edit Hospital forms — used by index.blade.php's
    Add/Edit offcanvases AND show.blade.php's Edit offcanvas (previously
    show.blade.php hand-rolled its own duplicate copy of these fields).

    Pass $hospital when editing so fields can be pre-filled server-side
    (show.blade.php); index.blade.php's Add form omits it (empty/old()-only)
    and its Edit offcanvas is populated client-side via JS instead.
--}}
@php($hospital = $hospital ?? null)

<div class="mb-4 text-center">
  <div class="avatar avatar-lg mb-2" id="logoPreviewWrap">
    <img id="logoPreview" src="{{ $hospital?->logo_url ?? '' }}" alt="" class="rounded {{ $hospital?->logo_url ? '' : 'd-none' }}" style="width:64px;height:64px;object-fit:cover;">
    <span id="logoPreviewFallback" class="avatar-initial rounded bg-label-primary {{ $hospital?->logo_url ? 'd-none' : '' }}" style="width:64px;height:64px;font-size:1.5rem;">
      <i class="icon-base ti tabler-building-hospital"></i>
    </span>
  </div>
  <label class="form-label d-block">Hospital Logo</label>
  <input type="file" class="form-control @error('logo') is-invalid @enderror" name="logo" accept="image/*"
    onchange="const f=this.files[0]; if(!f) return; const url=URL.createObjectURL(f); const img=this.closest('.mb-4').querySelector('#logoPreview'); const fb=this.closest('.mb-4').querySelector('#logoPreviewFallback'); img.src=url; img.classList.remove('d-none'); fb.classList.add('d-none');" />
  @error('logo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
</div>

<div class="mb-4">
  <label class="form-label">Hospital Name *</label>
  <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="City Care Hospital" value="{{ old('name', $hospital?->name) }}" required />
  @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-4">
  <label class="form-label">Contact Person</label>
  <input type="text" class="form-control @error('contact_person') is-invalid @enderror" name="contact_person" placeholder="e.g. Dr. Jane Silva" value="{{ old('contact_person', $hospital?->contact_person) }}" />
  @error('contact_person') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-4">
  <label class="form-label">Contact Email</label>
  <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $hospital?->email) }}" />
  @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-4">
  <label class="form-label">Phone</label>
  <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $hospital?->phone) }}" />
  @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
  <div class="col-6 mb-4">
    <label class="form-label">City</label>
    <input type="text" class="form-control" name="city" value="{{ old('city', $hospital?->city) }}" />
  </div>
  <div class="col-6 mb-4">
    <label class="form-label">Country</label>
    <input type="text" class="form-control" name="country" value="{{ old('country', $hospital?->country) }}" />
  </div>
</div>

<div class="row">
  <div class="col-6 mb-4">
    <label class="form-label">Plan *</label>
    <select class="form-select @error('plan') is-invalid @enderror" name="plan" required>
      @foreach(['basic' => 'Basic', 'professional' => 'Professional', 'enterprise' => 'Enterprise'] as $value => $label)
      <option value="{{ $value }}" @selected(old('plan', $hospital?->plan) === $value)>{{ $label }}</option>
      @endforeach
    </select>
    @error('plan') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
  <div class="col-6 mb-4">
    <label class="form-label">Status *</label>
    <select class="form-select @error('status') is-invalid @enderror" name="status" required>
      @foreach(['trial' => 'Trial', 'active' => 'Active', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled'] as $value => $label)
      <option value="{{ $value }}" @selected(old('status', $hospital?->status ?? 'active') === $value)>{{ $label }}</option>
      @endforeach
    </select>
    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
</div>

<div class="mb-4">
  <label class="form-label">Monthly Fee ({{ config('hospital.currency', 'LKR') }}) *</label>
  <input type="number" step="0.01" min="0" class="form-control @error('monthly_fee') is-invalid @enderror" name="monthly_fee" value="{{ old('monthly_fee', $hospital?->monthly_fee) }}" required />
  @error('monthly_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
  <div class="col-4 mb-4">
    <label class="form-label">Trial Ends</label>
    <input type="date" class="form-control" name="trial_ends_at" value="{{ old('trial_ends_at', optional($hospital?->trial_ends_at)->format('Y-m-d')) }}" />
  </div>
  <div class="col-4 mb-4">
    <label class="form-label">Sub. Starts</label>
    <input type="date" class="form-control" name="subscription_starts_at" value="{{ old('subscription_starts_at', optional($hospital?->subscription_starts_at)->format('Y-m-d')) }}" />
  </div>
  <div class="col-4 mb-4">
    <label class="form-label">Sub. Ends</label>
    <input type="date" class="form-control" name="subscription_ends_at" value="{{ old('subscription_ends_at', optional($hospital?->subscription_ends_at)->format('Y-m-d')) }}" />
  </div>
</div>

<div class="mb-4">
  <label class="form-label">Notes</label>
  <textarea class="form-control" name="notes" rows="2">{{ old('notes', $hospital?->notes) }}</textarea>
</div>
