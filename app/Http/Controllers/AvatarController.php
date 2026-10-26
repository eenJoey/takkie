<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Profielfoto's staan op de private disk; alleen jijzelf en leden van een gedeeld huishouden mogen ze zien
 * (← vinkie storage-policy "Avatars viewable by self or household").
 */
class AvatarController extends Controller
{
    public function show(User $user): StreamedResponse
    {
        abort_unless(auth()->user()->sharesHouseholdWith($user), 404);
        abort_if(! $user->avatar || str_starts_with($user->avatar, 'http'), 404);
        abort_unless(Storage::disk('local')->exists($user->avatar), 404);

        return Storage::disk('local')->response($user->avatar, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
