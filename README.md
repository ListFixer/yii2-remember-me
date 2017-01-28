# yii2-remember-me

This extension replaces the standard "Remember Me" identity cookie functionality of Yii2 with something similar to what is described here:  http://jaspan.com/improved_persistent_login_cookie_best_practice

When a user requests "Remember Me" during login, a new identity cookie is created for that user for that browser/computer.  The cookie contains three things which are also stored in a database table:  (1) a Cookie ID, which is the record number in the identity cookie database table, (2) A Cookie Key, which is the "password" for that particular cookie, and (3) A User Key, which is the "password" for the associated user.  When I say "password", it is a random string, not an actual password.  The database stores some other information, including the User ID number.

Each time a user restarts their browser and is authenticated using this system, all three items are checked against the database.  If the contents of the cookie match a record in the database, the user gains access to the system and a new User Key is generated.  The new User Key is stored in the database and in the identity cookie.

If a particular user uses three different computers, then there will be three different records in the database, one for each cookie.  When each of these cookies is used to authenticate a user, the User Key for that particular cookie is regenerated, leaving the other identity cookie User Keys unchanged.  This allows a particular user to have "Remember Me" functionality on multiple computers, yet still have their User Key change with each use.

If someone copies or steals an identity cookie, whichever cookie is used first (the original or the copy) will still work, since there is no way to determine which is the original and which is the copy.  The User Key will match and a new User Key will be generated.  Once the other cookie is used, the User Key will have already changed.  The Cookie ID and Cookie Key will match, but the User Key will not, thus indicating that more than one identity cookies exists with this Cookie ID and Cookie Key.  The database record is then deleted, thus disabling all cookies with this Cookie ID and Cookie Key.

To create the database table, use this command from your Yii2 application base directory:

php ./yii migrate --migrationPath=@vendor/listfixer/yii2-remember-me/migrations

The database migration assumes that you have a table called "user" with an integer primary key called "id".

To enable this extension, edit your configuration file to include this component information:

 'components' => [
    'user' => [
       'class' => 'listfixer\remember\RememberMe',
       'identityClass' => /* you should already have something here */,
       'enableAutoLogin' => true,
    ]
 ]

When a user changes their password, you can configure your system to disable all existing identity cookies for that user by invoking this method:

\listfixer\remember\models\UserIdentityCookie::deleteUserCookies( $this->id );

If you are using the Yii2 Advanced template, then this should be added to setPassword() in common/models/User.php.
