<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for Conversation message-visibility queries.
 *
 * Guards Conversation::visibleMessagesForUser() against leaking messages
 * from other conversations (a prior SQL operator-precedence bug made the
 * receiver branch ignore conversation_id).
 */
class ConversationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * visibleMessagesForUser() must only return messages from the given
     * conversation — never messages the user received in another chat.
     */
    public function test_visible_messages_are_scoped_to_the_conversation(): void
    {
        $member = User::factory()->create(['user_type' => 'member']);
        $escortA = User::factory()->create(['user_type' => 'escort']);
        $escortB = User::factory()->create(['user_type' => 'escort']);

        $conversationA = Conversation::create([
            'user_one_id' => $member->id,
            'user_two_id' => $escortA->id,
        ]);
        $conversationB = Conversation::create([
            'user_one_id' => $member->id,
            'user_two_id' => $escortB->id,
        ]);

        // Conversation A: one message from each side.
        Message::create([
            'conversation_id' => $conversationA->id,
            'sender_id' => $member->id,
            'receiver_id' => $escortA->id,
            'message' => 'member to escort A',
            'type' => 'text',
        ]);
        Message::create([
            'conversation_id' => $conversationA->id,
            'sender_id' => $escortA->id,
            'receiver_id' => $member->id,
            'message' => 'escort A to member',
            'type' => 'text',
        ]);

        // Conversation B: a received message that must NOT appear in A's list.
        Message::create([
            'conversation_id' => $conversationB->id,
            'sender_id' => $escortB->id,
            'receiver_id' => $member->id,
            'message' => 'escort B to member',
            'type' => 'text',
        ]);

        $visible = $conversationA->visibleMessagesForUser($member->id)
            ->pluck('id')
            ->all();

        $this->assertCount(2, $visible);
        $this->assertEqualsCanonicalizing(
            [1, 2],
            $visible,
        );
    }
}
