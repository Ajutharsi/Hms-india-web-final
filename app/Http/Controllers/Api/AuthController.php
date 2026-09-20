<?php

namespace App\Http\Controllers\Api;

use App\Actions\Fortify\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Patient;
use App\Models\User;
use App\Services\SubscriptionLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class AuthController extends Controller
{
    use PasswordValidationRules;

    /**
     * Issue a Sanctum bearer token for the Flutter app. Mirrors the
     * credential check in App\Actions\Fortify\AuthenticateUser (used by the
     * web login form) but returns a token instead of starting a session,
     * since the mobile app is a separate, non-cookie client.
     */
    public function login(Request $request)
    {
        $request->validate([
            Fortify::username() => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string'],
        ]);

        $user = User::where(
            Fortify::username(),
            $request->input(Fortify::username())
        )->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            ActivityLog::log(
                'login_failed',
                'auth',
                "Failed mobile login attempt for \"{$request->input(Fortify::username())}\"",
                $user
            );

            throw ValidationException::withMessages([
                'login' => ['Invalid email or password.'],
            ]);
        }

        $token = $user->createToken($request->input('device_name'))->plainTextToken;

        $user->forceFill(['last_login' => now()])->save();

        ActivityLog::log('login_success', 'auth', "Mobile login for \"{$user->email}\"", $user);

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Public patient self-registration for the Flutter app. Deliberately
     * separate from Fortify's own /register (used by the web login page):
     * that one only creates a bare User with the `patient` role — it does
     * NOT create the Patient clinical profile the rest of the app (Portal,
     * appointments, invoices, ...) actually keys off via
     * Patient::where('user_id', ...). This creates both together so a
     * self-registered patient is immediately usable everywhere else.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'unique:patients,email'],
            'password' => $this->passwordRules(),
            'phone' => ['nullable', 'string', 'max:15', 'regex:/^[0-9+\-\s()]+$/'],
            'dob' => ['nullable', 'date', 'before:today'],
            // Required, not merely validated-if-present: `patients.gender`
            // is a non-nullable DB column with no default (see
            // 2026_06_05_041443_create_patients_table.php) — omitting it
            // fails the insert with a raw SQL integrity-constraint error
            // rather than a clean validation message, so it's enforced here.
            'gender' => ['required', 'in:male,female,other'],
            'device_name' => ['required', 'string'],
        ]);

        try {
            app(SubscriptionLimitService::class)->assertCanCreate('patient');
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['email' => [$e->getMessage()]]);
        }

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
            $user->assignRole('patient');

            $patient = Patient::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'dob' => $data['dob'] ?? null,
                'gender' => $data['gender'],
                'patient_type' => 'op',
                'status' => 'active',
            ]);

            ActivityLog::log('created', 'Patient', 'Patient ' . $patient->mrn . ' self-registered via mobile app', $patient);

            return $user;
        });

        $token = $user->createToken($data['device_name'])->plainTextToken;
        $user->forceFill(['last_login' => now()])->save();

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ], 201);
    }

    /**
     * Sends the same "reset your password" email Fortify's web /forgot-password
     * form sends — the emailed link opens Fortify's existing
     * reset-password/{token} web page (not something the app needs to
     * handle itself) where the user sets a new password, then returns to
     * the app to sign in.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return response()->json(['message' => __($status)]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request)
    {
        return response()->json($this->userPayload($request->user()));
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'must_change_password' => (bool) $user->must_change_password,
            'profile_photo_url' => $user->profile_photo_url,
        ];
    }
}
