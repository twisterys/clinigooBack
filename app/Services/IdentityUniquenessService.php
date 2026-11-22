<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\PatientProfile;
use App\Models\ProfessionalProfile;
use App\Models\User;

class IdentityUniquenessService
{
    /**
     * Normalize an email address for case-insensitive comparison.
     */
    public static function normalizeEmail(?string $email): string
    {
        return strtolower(trim((string) $email));
    }

    /**
     * Normalize a CIN / ID number (uppercase, stripped of whitespace).
     */
    public static function normalizeCin(?string $cin): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim((string) $cin)) ?? '');
    }

    /**
     * Build equivalent canonical forms for a phone number (handles +212 / 00212 / 0x formats).
     *
     * @return list<string>
     */
    public static function phoneVariants(?string $phone): array
    {
        $raw = trim((string) $phone);
        if ($raw === '') {
            return [];
        }

        $compact = preg_replace('/[\s\-\(\)\.]+/', '', $raw) ?? '';
        if ($compact === '') {
            return [];
        }

        $variants = [$compact];

        // Normalize Moroccan international / local prefixes so +2126..., 002126..., 2126..., 06... match
        if (str_starts_with($compact, '+212') && strlen($compact) >= 13) {
            $local = '0'.substr($compact, 4);
            $variants[] = $local;
            $variants[] = '00212'.substr($compact, 4);
            $variants[] = '212'.substr($compact, 4);
        } elseif (str_starts_with($compact, '00212') && strlen($compact) >= 14) {
            $local = '0'.substr($compact, 5);
            $variants[] = $local;
            $variants[] = '+212'.substr($compact, 5);
            $variants[] = '212'.substr($compact, 5);
        } elseif (str_starts_with($compact, '212') && strlen($compact) === 12) {
            $local = '0'.substr($compact, 3);
            $variants[] = $local;
            $variants[] = '+'.$compact;
            $variants[] = '00'.$compact;
        } elseif (str_starts_with($compact, '0') && strlen($compact) === 10) {
            $rest = substr($compact, 1);
            $variants[] = '+212'.$rest;
            $variants[] = '00212'.$rest;
            $variants[] = '212'.$rest;
        }

        return array_values(array_unique($variants));
    }

    /**
     * Check whether an email is already used by any user (patient, professional, or admin)
     * or any patient profile.
     */
    public static function isEmailTaken(?string $email, ?int $ignoreUserId = null): bool
    {
        $norm = self::normalizeEmail($email);
        if ($norm === '') {
            return false;
        }

        $userQuery = User::whereRaw('LOWER(TRIM(email)) = ?', [$norm]);
        if ($ignoreUserId !== null) {
            $userQuery->where('id', '!=', $ignoreUserId);
        }
        if ($userQuery->exists()) {
            return true;
        }

        $patientQuery = PatientProfile::whereNotNull('email')
            ->whereRaw('LOWER(TRIM(email)) = ?', [$norm]);
        if ($ignoreUserId !== null) {
            $patientQuery->where('user_id', '!=', $ignoreUserId);
        }

        return $patientQuery->exists();
    }

    /**
     * Check whether a phone number is already used by any patient or professional.
     */
    public static function isPhoneTaken(?string $phone, ?int $ignoreUserId = null): bool
    {
        $variants = self::phoneVariants($phone);
        if (empty($variants)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($variants), '?'));
        $normSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), '.', '')";

        $proQuery = ProfessionalProfile::whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereRaw("{$normSql} IN ({$placeholders})", $variants);
        if ($ignoreUserId !== null) {
            $proQuery->where('user_id', '!=', $ignoreUserId);
        }
        if ($proQuery->exists()) {
            return true;
        }

        $patientQuery = PatientProfile::whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereRaw("{$normSql} IN ({$placeholders})", $variants);
        if ($ignoreUserId !== null) {
            $patientQuery->where('user_id', '!=', $ignoreUserId);
        }

        return $patientQuery->exists();
    }

    /**
     * Check whether a CIN / ID number is already used by any professional or patient (default beneficiary).
     */
    public static function isCinTaken(?string $cin, ?int $ignoreUserId = null): bool
    {
        $norm = self::normalizeCin($cin);
        if ($norm === '') {
            return false;
        }

        $proQuery = ProfessionalProfile::whereNotNull('id_number')
            ->where('id_number', '!=', '')
            ->whereRaw("UPPER(REPLACE(TRIM(id_number), ' ', '')) = ?", [$norm]);
        if ($ignoreUserId !== null) {
            $proQuery->where('user_id', '!=', $ignoreUserId);
        }
        if ($proQuery->exists()) {
            return true;
        }

        $beneficiaryQuery = Beneficiary::where('is_default', true)
            ->whereNotNull('cin_amo_cnss')
            ->where('cin_amo_cnss', '!=', '')
            ->whereRaw("UPPER(REPLACE(TRIM(cin_amo_cnss), ' ', '')) = ?", [$norm]);
        if ($ignoreUserId !== null) {
            $beneficiaryQuery->where('user_id', '!=', $ignoreUserId);
        }

        return $beneficiaryQuery->exists();
    }
}
