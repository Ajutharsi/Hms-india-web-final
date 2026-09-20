<?php

namespace App\Console\Commands;

use App\Models\Patient;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptExistingPatients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:encrypt-existing-patients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt plaintext patient PII (name, phone, email, dob, blood_group, address, emergency contact) at rest and populate blind-index hash columns';

    private const ENCRYPTED_FIELDS = [
        'name', 'phone', 'email', 'dob', 'blood_group',
        'address', 'emergency_contact', 'emergency_contact_name',
    ];

    private const HASHED_FIELDS = ['name', 'phone', 'email'];

    /**
     * Execute the console command.
     *
     * Reads/writes via the query builder (not the Eloquent model) so that
     * already-encrypted rows and still-plaintext rows can both be inspected
     * safely — going through Patient's 'encrypted' cast here would throw on
     * any row not yet encrypted.
     */
    public function handle(): int
    {
        $patients = DB::table('patients')->get();

        $encrypted = 0;
        $skipped   = 0;

        foreach ($patients as $patient) {
            $updates = [];

            foreach (self::ENCRYPTED_FIELDS as $field) {
                $value = $patient->{$field};

                if ($value === null || $value === '') {
                    continue;
                }

                if ($this->isAlreadyEncrypted($value)) {
                    continue;
                }

                $updates[$field] = Crypt::encryptString($value);

                if (in_array($field, self::HASHED_FIELDS, true)) {
                    $updates[$field . '_hash'] = Patient::hashFor($value);
                }
            }

            if (empty($updates)) {
                $skipped++;
                continue;
            }

            DB::table('patients')->where('id', $patient->id)->update($updates);
            $encrypted++;
        }

        $this->info("Encrypted {$encrypted} patient(s).");
        $this->info("Skipped {$skipped} patient(s) already encrypted.");

        return self::SUCCESS;
    }

    private function isAlreadyEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);
            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}
