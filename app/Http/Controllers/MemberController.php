<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Inertia\Inertia;

/**
 * Public member profile views — lets a chat partner (escort) see basic
 * profile info about the member they are talking to.
 */
class MemberController extends Controller
{
    /* -----------------------------------------------------------------
     | Member Profile View
     |-----------------------------------------------------------------*/

    /**
     * Render the public member profile page (Frontend/Member/Show).
     *
     * @return \Inertia\Response
     */
    public function show(Member $member)
    {
        // Only expose members backed by a valid member-type user — the
        // user_type discriminator keeps escorts/system users out of this page.
        abort_unless($member->user && $member->user->isMember(), 404);

        $member->load(['user', 'user.county', 'user.town']);

        $user = $member->user;

        return Inertia::render('Frontend/Member/Show', [
            'member' => [
                'id' => $member->id,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'display_name' => $user->display_name,
                    'gender' => $user->gender,
                    'profile_photo_url' => $user->profile_photo_url,
                    'county' => $user->county ? $user->county->name : null,
                    'town' => $user->town ? $user->town->name : null,
                    'member_since' => $user->created_at,
                ],
            ],
        ]);
    }
}
