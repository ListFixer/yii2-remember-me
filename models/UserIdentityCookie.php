<?php namespace listfixer\remember\models;

use yii;
use yii\db\ActiveRecord;

class UserIdentityCookie extends ActiveRecord
{
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
		   return false;
		}

		if ($insert) {
		   // The cookie_key authenticates an identity cookie and it does not change.
		   // A particular user will have a different identity cookie for each browser
		   // on each computer where cookie-based logins are enabled and requested.
		   // A stolen identity cookie will have a valid cookie_key.
		   // A fake identity cookie will NOT have a valid cookie_key.
		   $this->cookie_key = Yii::$app->security->generateRandomString();
		   $this->first_login = time();
		}

		// The user_key authenticates the user.  It is different for each identity cookie
		// and it changes after each successful cookie-based login.  If an identity cookie
		// is copied or stolen, the next cookie-based login will succeed (using the stolen
		// copy or using the original) and the user_key will be updated in that identity
		// cookie and in the database.  The user_key in the other identity cookie will now
		// be out-of-date.  If an attempt is made to use the other identity cookie, the
		// invalid user_key will indicate a stolen or copied identity cookie and the associated
		// database record will be deleted.  This will disable both the original and the copy,
		// since we do not know which cookie is the original and which is the copy.
		$this->user_key = Yii::$app->security->generateRandomString();
		$this->last_login = time();
		$this->last_ip = Yii::$app->getRequest()->getUserIP();
		$this->last_agent = Yii::$app->getRequest()->getUserAgent();

		return true;
    }

    public static function findAndValidate($cookie_id, $cookie_key, $any_user, $user_id, $user_key)
    {
        $identity_cookie = static::findOne([ 'id' => $cookie_id, 'cookie_key' => $cookie_key ]);
    
        if ($identity_cookie === null) {
           return null;
        }

        if (($any_user || $identity_cookie->user_id == $user_id) && $identity_cookie->user_key == $user_key && $identity_cookie->last_login + $identity_cookie->duration >= time()) {
           return $identity_cookie;
        }

        // If cookie found was for another user, the user_key was incorrect, or it has expired, then delete the database record for this cookie.
        $identity_cookie->delete();

        return null;
    }

    // This can be run by a user to disable all identity cookies.
    // This should be run after a user changes their password.
    public static function deleteUserCookies($user_id)
    {
        static::deleteAll([ 'user_id' => $user_id ]);
    }

    // This can be run periodically to clean up the database.
    public static function deleteExpiredCookies()
    {
        static::deleteAll('last_login + duration < :time', [ ':time' => time() ]);
    }
}
