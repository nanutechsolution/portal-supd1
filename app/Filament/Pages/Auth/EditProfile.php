<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use SensitiveParameter;

class EditProfile extends BaseEditProfile
{
    protected static ?string $slug = 'ubah-kata-sandi';

    protected static ?string $title = 'Ubah Kata Sandi';

    public bool $wasPasswordChangeRequired = false;

    public function mount(): void
    {
        $user = Filament::auth()->user();

        $this->wasPasswordChangeRequired = $user instanceof User && $user->isPasswordChangeRequired();

        parent::mount();
    }

    public function isPasswordChangeRequired(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->isPasswordChangeRequired();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Gunakan kata sandi yang kuat dan tidak dipakai di layanan online lainnya.';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Callout::make('Wajib mengganti kata sandi')
                    ->description('Akun Anda masih menggunakan kata sandi sementara. Ganti dengan kata sandi pribadi Anda sebelum melanjutkan ke panel.')
                    ->warning()
                    ->visible(fn (): bool => $this->isPasswordChangeRequired()),
                $this->getFormContentComponent(),
                ...Arr::wrap($this->getMultiFactorAuthenticationContentComponent()),
            ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->inlineLabel(! static::isSimple())
            ->model($this->getUser())
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $this->defaultForm($schema)->components([
            $this->getCurrentPasswordFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
        ]);
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return parent::getCurrentPasswordFormComponent()
            ->label('Kata sandi saat ini')
            ->helperText('Masukkan kata sandi yang sedang Anda pakai untuk memverifikasi perubahan ini.')
            ->visible(true)
            ->required()
            ->autofocus();
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Kata sandi baru')
            ->validationAttribute('kata sandi baru')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->rule(Password::min(10)->mixedCase()->numbers())
            ->showAllValidationMessages()
            ->required()
            ->same('passwordConfirmation')
            ->different('currentPassword')
            ->autocomplete('new-password')
            ->helperText('Minimal 10 karakter, memakai huruf besar, huruf kecil, dan angka.')
            ->dehydrated(fn (#[SensitiveParameter] $state): bool => filled($state))
            ->dehydrateStateUsing(fn (#[SensitiveParameter] $state): string => Hash::make($state))
            ->live(debounce: 500)
            ->validationMessages([
                'required' => 'Kata sandi baru wajib diisi.',
                'min' => 'Kata sandi baru harus memiliki minimal :min karakter.',
                'mixed' => 'Kata sandi baru harus memakai huruf besar dan huruf kecil.',
                'numbers' => 'Kata sandi baru harus memakai angka.',
                'same' => 'Konfirmasi kata sandi belum cocok.',
                'different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
            ]);
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return parent::getPasswordConfirmationFormComponent()
            ->label('Konfirmasi kata sandi baru')
            ->helperText('Ketik ulang kata sandi baru untuk memastikan tidak terjadi salah ketik.');
    }

    protected function getRedirectUrl(): ?string
    {
        if (! $this->wasPasswordChangeRequired) {
            return null;
        }

        return filament()->getCurrentPanel()?->getUrl();
    }

    protected function handleRecordUpdate(Model $record, #[SensitiveParameter] array $data): Model
    {
        if (array_key_exists('password', $data)) {
            $data['must_change_password'] = false;
            $data['password_changed_at'] = now();
        }

        return parent::handleRecordUpdate($record, $data);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Kata sandi berhasil diperbarui';
    }
}
