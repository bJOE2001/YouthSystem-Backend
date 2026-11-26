<?php

namespace App\Actions\YouthProfile;

use App\Enums\YouthProfileStatus;
use App\Models\YouthProfile;
use App\Models\YouthValidation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FindMatchingYouthProfileAction
{
    /**
     * Match incoming registration/profile data against existing non-rejected youth profiles
     * and pending youth validations using Primary (First + Middle + Last + Date of Birth)
     * and Supporting (Suffix + Sex + Mobile + Address) attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(
        array $attributes,
        ?int $ignoreYouthProfileId = null,
        ?int $ignoreYouthValidationId = null,
    ): YouthProfile|YouthValidation|null {
        $birthDate = $this->normalizeDate($attributes['birth_date'] ?? $attributes['birthdate'] ?? null);
        if ($birthDate === null) {
            return null;
        }

        [$incomingFirst, $incomingMiddle, $incomingLast, $incomingSuffix] = $this->extractNameParts($attributes);
        if ($incomingFirst === '' || $incomingLast === '') {
            return null;
        }

        $incomingSex = $this->normalizeText($attributes['gender'] ?? $attributes['sex'] ?? null);
        $incomingPhone = $this->normalizePhone($attributes['mobile_number'] ?? $attributes['mobileNumber'] ?? null);
        $incomingAddress = $this->normalizeAddress($attributes);

        $profileCandidates = YouthProfile::query()
            ->where('status', '!=', YouthProfileStatus::Rejected->value)
            ->whereDate('birth_date', $birthDate)
            ->when($ignoreYouthProfileId !== null, fn ($q) => $q->where('id', '!=', $ignoreYouthProfileId))
            ->get();

        $matchedProfile = $this->findMatchingCandidate(
            $profileCandidates,
            $incomingFirst,
            $incomingMiddle,
            $incomingLast,
            $incomingSuffix,
            $incomingSex,
            $incomingPhone,
            $incomingAddress,
        );

        if ($matchedProfile !== null) {
            return $matchedProfile;
        }

        $validationCandidates = YouthValidation::query()
            ->where('status', YouthProfileStatus::Pending->value)
            ->whereDate('birth_date', $birthDate)
            ->when($ignoreYouthValidationId !== null, fn ($q) => $q->where('id', '!=', $ignoreYouthValidationId))
            ->get();

        return $this->findMatchingCandidate(
            $validationCandidates,
            $incomingFirst,
            $incomingMiddle,
            $incomingLast,
            $incomingSuffix,
            $incomingSex,
            $incomingPhone,
            $incomingAddress,
        );
    }

    /**
     * @param  Collection<int, YouthProfile|YouthValidation>  $candidates
     * @param  array{barangay: string, purok_sitio: string, city: string, province: string}  $incomingAddress
     */
    private function findMatchingCandidate(
        Collection $candidates,
        string $incomingFirst,
        string $incomingMiddle,
        string $incomingLast,
        string $incomingSuffix,
        string $incomingSex,
        string $incomingPhone,
        array $incomingAddress,
    ): YouthProfile|YouthValidation|null {
        foreach ($candidates as $candidate) {
            [$candFirst, $candMiddle, $candLast, $candSuffix] = $this->extractNameParts([
                'first_name' => $candidate->first_name,
                'middle_name' => $candidate->middle_name,
                'last_name' => $candidate->last_name,
                'suffix' => $candidate->suffix,
            ]);

            if ($incomingFirst !== $candFirst || $incomingLast !== $candLast) {
                continue;
            }

            $exactMiddleMatch = $incomingMiddle === $candMiddle;
            $compatibleMiddle = $exactMiddleMatch || $this->isCompatibleMiddleName($incomingMiddle, $candMiddle);

            if (! $compatibleMiddle) {
                continue;
            }

            // Explicit non-empty suffix mismatch (e.g., Jr vs III) indicates distinct individuals
            if ($incomingSuffix !== '' && $candSuffix !== '' && $incomingSuffix !== $candSuffix) {
                continue;
            }

            $supportingMatches = 0;

            if ($incomingSuffix !== '' && $incomingSuffix === $candSuffix) {
                $supportingMatches++;
            }

            $candSex = $this->normalizeText($candidate->gender);
            if ($incomingSex !== '' && $incomingSex === $candSex) {
                $supportingMatches++;
            }

            $candPhone = $this->normalizePhone($candidate->mobile_number);
            if ($incomingPhone !== '' && $incomingPhone === $candPhone) {
                $supportingMatches++;
            }

            $candAddress = $this->normalizeAddress([
                'barangay' => $candidate->barangay,
                'purok_sitio' => $candidate->purok_sitio,
                'city' => $candidate->city,
                'province' => $candidate->province,
            ]);
            if ($this->addressesMatch($incomingAddress, $candAddress)) {
                $supportingMatches++;
            }

            if ($exactMiddleMatch && $supportingMatches >= 1) {
                return $candidate;
            }

            if (! $exactMiddleMatch && $supportingMatches >= 2) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function extractNameParts(array $attributes): array
    {
        $first = $this->normalizeText($attributes['first_name'] ?? $attributes['firstName'] ?? null);
        $middle = $this->normalizeText($attributes['middle_name'] ?? $attributes['middleName'] ?? null);
        $rawLast = (string) ($attributes['last_name'] ?? $attributes['lastName'] ?? '');
        $suffix = $this->normalizeSuffix($attributes['suffix'] ?? null);

        if ($suffix === '' && preg_match('/\s+(Jr\.?|Sr\.?|I{1,3}|IV|V|VI|VII|VIII)\b$/i', trim($rawLast), $matches)) {
            $suffix = $this->normalizeSuffix($matches[1]);
            $rawLast = preg_replace('/\s+(Jr\.?|Sr\.?|I{1,3}|IV|V|VI|VII|VIII)\b$/i', '', trim($rawLast)) ?? $rawLast;
        }

        $last = $this->normalizeText($rawLast);

        return [$first, $middle, $last, $suffix];
    }

    private function isCompatibleMiddleName(string $a, string $b): bool
    {
        if ($a === '' || $b === '') {
            return true;
        }

        $aClean = str_replace('.', '', $a);
        $bClean = str_replace('.', '', $b);

        if (mb_strlen($aClean, 'UTF-8') === 1 && str_starts_with($bClean, $aClean)) {
            return true;
        }

        if (mb_strlen($bClean, 'UTF-8') === 1 && str_starts_with($aClean, $bClean)) {
            return true;
        }

        return false;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof Carbon || $value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse(trim($value))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeText(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $collapsed = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return mb_strtolower($collapsed, 'UTF-8');
    }

    private function normalizeSuffix(mixed $value): string
    {
        $normalized = str_replace('.', '', $this->normalizeText($value));

        if (in_array($normalized, ['', 'none', 'n/a', 'na'], true)) {
            return '';
        }

        return $normalized;
    }

    private function normalizePhone(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return '';
        }

        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (str_starts_with($digits, '639') && strlen($digits) === 12) {
            return '0'.substr($digits, 2);
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '0'.$digits;
        }

        return $digits;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{barangay: string, purok_sitio: string, city: string, province: string}
     */
    private function normalizeAddress(array $attributes): array
    {
        return [
            'barangay' => $this->normalizeText($attributes['barangay'] ?? null),
            'purok_sitio' => $this->normalizeText($attributes['purok_sitio'] ?? $attributes['purokSitio'] ?? null),
            'city' => $this->normalizeText($attributes['city'] ?? null),
            'province' => $this->normalizeText($attributes['province'] ?? null),
        ];
    }

    /**
     * @param  array{barangay: string, purok_sitio: string, city: string, province: string}  $a
     * @param  array{barangay: string, purok_sitio: string, city: string, province: string}  $b
     */
    private function addressesMatch(array $a, array $b): bool
    {
        if ($a['barangay'] === '' || $a['barangay'] !== $b['barangay']) {
            return false;
        }

        if ($a['purok_sitio'] === '' || $a['purok_sitio'] !== $b['purok_sitio']) {
            return false;
        }

        if ($a['city'] !== '' && $b['city'] !== '' && $a['city'] !== $b['city']) {
            return false;
        }

        if ($a['province'] !== '' && $b['province'] !== '' && $a['province'] !== $b['province']) {
            return false;
        }

        return true;
    }
}
