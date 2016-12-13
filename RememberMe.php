<?php namespace listfixer\remember;

use yii;

use listfixer\remember\models\UserIdentityCookie;

class RememberMe extends yii\web\User
{
    // Save "Remember Me" information.
    protected function sendIdentityCookie($identity, $duration)
    {
        $user_id = $identity->getId();

        // Reuse the existing identity cookie if it is for this user.
        $identity_cookie = $this->examineIdentityCookie(false, $user_id);

        if ($identity_cookie === null) {
            $identity_cookie = new UserIdentityCookie;
            $identity_cookie->user_id = $user_id;
            $identity_cookie->duration = $duration;
        }

        if ($identity_cookie->save()) {
            $cookie = new Yii\web\Cookie($this->identityCookie);
            $cookie->value = json_encode(
                [ $identity_cookie->id, $identity_cookie->cookie_key, $identity_cookie->user_key ],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );

            $cookie->expire = time() + $identity_cookie->duration;
            Yii::$app->response->cookies->add($cookie);
        }
    }

    protected function renewIdentityCookie()
    {
    	// Cookies are only renewed during a "Remember Me" login.
    }

    // Attempt "Remember Me" login.
    protected function getIdentityAndDurationFromCookie()
    {
        $identity_cookie = $this->examineIdentityCookie();

        if ($identity_cookie === null) {
            return null;
        }

        $class = $this->identityClass;
        $identity = $class::findIdentity($identity_cookie->user_id);

        if ($identity === null) {
            return null;
        }

        return [ 'identity' => $identity, 'duration' => $identity_cookie->duration ];
    }

    // Remove "Remember Me" cookie if it isn't a current cookie.
    protected function removeIdentityCookie()
    {
        $this->examineIdentityCookie(false,$this->getId());
    }

    // Attempt to retrieve "Remember Me" information from cookie.
    protected function examineIdentityCookie($any_user = true, $user_id = null)
    {
        $value = Yii::$app->request->cookies->getValue($this->identityCookie['name']);

        if ($value === null) {
            return null;
        }

        $data = json_decode($value, true);

        if (count($data) == 3 && is_numeric($data[0])) {
            $user_identity_cookie = UserIdentityCookie::findAndValidate(
                $data[0],   // cookie_id
                $data[1],   // cookie_key
                $any_user,
                $user_id,
                $data[2]    // user_key
            );

            if ($user_identity_cookie !== null) {
                return $user_identity_cookie;
            }
        }

        // If the cookie information is not usable, then delete the cookie.
        Yii::$app->response->cookies->remove($this->identityCookie['name']);

        return null;
    }
}
