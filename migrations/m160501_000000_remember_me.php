<?php

use yii\db\Schema;
use yii\db\Migration;

class m160501_000000_remember_me extends Migration {

    public function up( )
    {
        $this->createTable( 'user_identity_cookie', [
            'id' => 'pk',
            'user_id' => Schema::TYPE_INTEGER . ' not null',
            'duration' => Schema::TYPE_INTEGER . ' not null',
            'first_login' => Schema::TYPE_INTEGER . ' not null',
            'last_login' => Schema::TYPE_INTEGER . ' not null',
            'cookie_key' => Schema::TYPE_STRING . '(64) not null',
            'user_key' => Schema::TYPE_STRING . '(64) not null',
            'last_ip' => Schema::TYPE_STRING . '(40) null',
            'last_agent' => Schema::TYPE_STRING . '(1024) null'
        ] );
        $this->addForeignKey( 'user_identity_cookie_foreign', 'user_identity_cookie', 'user_id', 'user', 'id', 'CASCADE', 'CASCADE' );
    }
        
    public function down( )
    {
        $this->dropTable( 'user_identity_cookie' );
    }
}
