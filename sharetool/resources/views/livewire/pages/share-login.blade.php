<?php
declare(strict_types=1);

use App\Models\Share;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
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

        $share = Share::where('id', $this->shareId)->firstOrFail();
        Gate::authorize('viewShareLogin', [$share, $this->publicToken]);
        return $share;
    }

    #[Validate('required|string')]
    public string $password = '';

    public function login(): void
    {
        $this->validate();
        $share = $this->share();
        $throttleKey = $share->id . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'password' => __('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        if (!$share->password || !Hash::check($this->password, $share->password)) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages(['password' => __('Invalid password')]);
        }

        // store the password hash so that if the share owner changes the password,
        // the existing sessions are no longer valid
        session(["share-password.$share->id" => $share->password]);

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
