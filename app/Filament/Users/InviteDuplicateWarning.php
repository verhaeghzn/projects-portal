<?php

namespace App\Filament\Users;

use App\Models\User;
use Closure;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InviteDuplicateWarning
{
    public static function notice(): View
    {
        return View::make('filament.users.invite-duplicate-warning')
            ->viewData(fn (Get $get): array => [
                'matches' => self::matches($get('name'), $get('email')),
                'email' => $get('email'),
            ])
            ->columnSpanFull();
    }

    public static function confirmation(): Checkbox
    {
        return Checkbox::make('confirm_not_duplicate')
            ->label('I checked the accounts above. This is a different person.')
            ->visible(fn (Get $get): bool => self::similarAccounts($get('name'), $get('email'))->isNotEmpty())
            ->accepted(fn (Get $get): bool => self::similarAccounts($get('name'), $get('email'))->isNotEmpty())
            ->validationMessages([
                'accepted' => 'Check the existing accounts before inviting. Tick the box only if this is a different person.',
            ]);
    }

    public static function emailAlreadyRegistered(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $existing = User::findByEmailForSaml((string) $value);

            if ($existing === null) {
                return;
            }

            $fail('This email address is already used by '.$existing->name.'.');
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function blockReason(array $data): ?string
    {
        $email = (string) ($data['email'] ?? '');
        $existing = $email !== '' ? User::findByEmailForSaml($email) : null;

        if ($existing !== null) {
            return $existing->name.' already uses this email address.';
        }

        if (self::similarAccounts($data['name'] ?? null, $email)->isNotEmpty() && empty($data['confirm_not_duplicate'])) {
            return 'This person may already have an account. Confirm that this is a different person before inviting.';
        }

        return null;
    }

    public static function statusLabel(User $user): string
    {
        if (self::isPendingActivation($user)) {
            return 'Pending activation';
        }

        return $user->email_verified_at ? 'Activated' : 'Inactive';
    }

    public static function statusColor(User $user): string
    {
        if (self::isPendingActivation($user)) {
            return 'warning';
        }

        return $user->email_verified_at ? 'success' : 'gray';
    }

    private static function isPendingActivation(User $user): bool
    {
        return $user->email_verified_at === null && $user->invitation_token !== null;
    }

    public static function isSameEmail(User $user, ?string $email): bool
    {
        $email = mb_strtolower(trim((string) $email));

        return $email !== '' && mb_strtolower($user->email) === $email;
    }

    /**
     * @return Collection<int, User>
     */
    public static function matches(?string $name, ?string $email): Collection
    {
        $name = trim((string) $name);
        $email = mb_strtolower(trim((string) $email));
        $nameMatch = mb_strlen($name) >= 3 ? mb_strtolower($name) : null;
        $localPart = strstr($email, '@', true) ?: '';
        $localPartMatch = self::localPartWorthMatching($localPart) ? $localPart : null;

        if ($email === '' && $nameMatch === null) {
            return collect();
        }

        return User::query()
            ->with('group')
            ->where(function (Builder $query) use ($email, $nameMatch, $localPartMatch): void {
                if ($email !== '') {
                    $query->orWhereRaw('LOWER(email) = ?', [$email]);
                }

                if ($localPartMatch !== null) {
                    $query->orWhereRaw('LOWER(email) LIKE ?', [$localPartMatch.'@%']);
                }

                if ($nameMatch !== null) {
                    $query->orWhereRaw('LOWER(name) = ?', [$nameMatch]);
                }
            })
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    /**
     * Accounts that look like the same person, excluding an exact email match.
     * An exact email is already rejected and does not need a separate confirmation.
     *
     * @return Collection<int, User>
     */
    public static function similarAccounts(?string $name, ?string $email): Collection
    {
        $email = mb_strtolower(trim((string) $email));

        return self::matches($name, $email)
            ->reject(fn (User $user): bool => self::isSameEmail($user, $email))
            ->values();
    }

    private static function localPartWorthMatching(string $localPart): bool
    {
        return mb_strlen($localPart) >= 5
            && str_contains($localPart, '.')
            && ! str_contains($localPart, '%')
            && ! str_contains($localPart, '_');
    }
}
