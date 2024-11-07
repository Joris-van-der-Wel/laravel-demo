<?php
declare(strict_types=1);

use App\Exceptions\ShareInvalidPasswordException;
use App\Exceptions\ShareLoginRateLimited;
use App\Models\Share;
use Facades\App\Services\ShareAuthorization;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new
#[Layout('layouts.app')]
class extends Component {
    #[Locked]
    public string $shareId; // from route

    #[Locked]
    public ?string $publicToken = null; // from route

    #[Computed]
    public function share(): Share
    {
        // This component is used in two routes. The first requires a session using the regular
        // auth middleware. The second does not check for a session, but requires a publicToken
        return ShareAuthorization::authorizeShare($this->shareId, $this->publicToken, skipPasswordCheck: true);
    }

    #[Validate('required|string')]
    public string $password = '';

    public function login(): void
    {
        $this->validate();

        $share = $this->share();
        try {
            ShareAuthorization::shareLogin($share, $this->password);
        } catch (ShareLoginRateLimited $ex) {
            throw ValidationException::withMessages([
                'password' => __('auth.throttle', [
                    'seconds' => $ex->availableInSeconds,
                    'minutes' => ceil($ex->availableInSeconds / 60),
                ]),
            ]);
        } catch (ShareInvalidPasswordException) {
            throw ValidationException::withMessages(['password' => __('Invalid password')]);
        }

        if ($this->publicToken) {
            $url = route('publicShare.details', ['shareId' => $share->id, 'publicToken' => $this->publicToken]);
        } else {
            $url = route('share.details', ['shareId' => $share->id]);
        }
        $this->redirect($url);
    }
}
?>

<form wire:submit="login">
    <div class="max-w-xl mx-auto my-16 bg-white p-8">
        <div>
            <p class="my-4">
                {{ __('This share requires a password to access.') }}
            </p>

            <div class="my-2">
                <x-input-label for="password" :value="__('Password')"/>

                <x-text-input
                    wire:model="password"
                    id="password"
                    class="block mt-1 w-full"
                    type="password"
                    name="password"
                    required
                />

                <x-input-error :messages="$errors->get('password')" class="mt-2"/>
            </div>

            <x-primary-button class="my-2">
                {{ __('Access share') }}
            </x-primary-button>
        </div>
    </div>
</form>
