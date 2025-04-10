<?php

namespace humhub\modules\social_bulk_invite\jobs;

use humhub\modules\user\models\Invite;
use humhub\modules\queue\LongRunningActiveJob;
use Yii;

/**
 * Job for sending user invitations
 */
class InviteJob extends LongRunningActiveJob
{
    /**
     * @var string email address of the invited user
     */
    public $inviteEmail;

    /**
     * @var int space ID to invite the user to
     */
    public $inviteSpace;

    /**
     * @var int user ID of the person who originated the invitation
     */
    public $inviteOrigin;

    /**
     * @var string language code for the invitation
     */
    public $inviteLanguage = 'en';

    /**
     * @inheritdoc
     */
    public function run()
    {
        // Store current application language
        $currentLanguage = Yii::$app->language;

        try {
            // Set language for this job execution
            Yii::$app->language = $this->inviteLanguage;

            $userInvite = new Invite(); 
            $userInvite->email = $this->inviteEmail; 
            $userInvite->source = 'invite'; 
            $userInvite->user_originator_id = $this->inviteOrigin; 
            $userInvite->space_invite_id = $this->inviteSpace;
            $userInvite->language = $this->inviteLanguage;

            if ($userInvite->validate() && $userInvite->save()) {
                return $userInvite->sendInviteMail();
            }

            return false;
        } catch (\Exception $e) {
            Yii::error('Error executing invite job: ' . $e->getMessage(), 'social_bulk_invite');
            return false;
        } finally {
            // Restore original language
            Yii::$app->language = $currentLanguage;
        }
    }

    /**
     * @inheritdoc
     */
    public function canRetry($attempt, $error)
    {
        // Allow up to 3 retry attempts
        return ($attempt < 3);
    }
}