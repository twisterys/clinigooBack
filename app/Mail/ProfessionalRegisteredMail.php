<?php

namespace App\Mail;

use App\Enums\HealthcareProfession;
use App\Models\Profession;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ProfessionalRegisteredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Number of attempts before marking the job as failed.
     */
    public int $tries = 3;

    /**
     * Backoff intervals (seconds) between retries.
     *
     * @var list<int>
     */
    public array $backoff = [10, 60, 180];

    public string $recipientName;

    public string $recipientEmail;

    public string $professionName;

    public ?string $city;

    public function __construct(
        User $user,
        ?ProfessionalProfile $profile,
        public string $otpCode,
        public string $verificationUrl,
    ) {
        $this->afterCommit();

        $this->recipientName = trim((string) ($profile?->full_name ?: $user->name));
        $this->recipientEmail = $user->email;
        $this->professionName = $this->resolveProfessionName($user, $profile);
        $this->city = $profile?->city ?: null;
    }

    protected function resolveProfessionName(User $user, ?ProfessionalProfile $profile): string
    {
        if ($profile) {
            $relation = $profile->relationLoaded('profession')
                ? $profile->getRelation('profession')
                : ($profile->profession_id ? Profession::find($profile->profession_id) : null);

            if ($relation instanceof Profession && ! empty($relation->name)) {
                return $relation->name;
            }

            $rawProfileProfession = $profile->getRawOriginal('profession');
            if ($rawProfileProfession !== null && $rawProfileProfession !== '') {
                $enum = HealthcareProfession::fromInput($rawProfileProfession);
                if ($enum !== null) {
                    return $enum->label();
                }
            }
        }

        if ($user->profession instanceof HealthcareProfession) {
            return $user->profession->label();
        }

        $rawUserProfession = $user->getRawOriginal('profession');
        if ($rawUserProfession !== null && $rawUserProfession !== '') {
            $enum = HealthcareProfession::fromInput($rawUserProfession);
            if ($enum !== null) {
                return $enum->label();
            }
        }

        return 'Professionnel de santé';
    }

    /**
     * Build a frontend verification URL carrying the backend signed route parameters.
     */
    public static function buildVerificationUrl(User $user, int $ttlMinutes = 15): string
    {
        $hash = sha1($user->email);
        $signedBackendUrl = URL::temporarySignedRoute(
            'api.professional.verify-email.link',
            now()->addMinutes($ttlMinutes),
            [
                'id' => $user->id,
                'hash' => $hash,
            ],
            absolute: false
        );

        $query = [];
        parse_str((string) parse_url($signedBackendUrl, PHP_URL_QUERY), $query);

        $frontendBase = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');

        return $frontendBase.'/professional/verify-email?'.http_build_query([
            'id' => $user->id,
            'hash' => $hash,
            'expires' => $query['expires'] ?? '',
            'signature' => $query['signature'] ?? '',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vérifiez votre adresse email — Clinigoo',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.professional-registered',
        );
    }
}
