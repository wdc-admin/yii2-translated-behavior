<?php

use yii\db\Migration;
use lav45\translate\models\Lang;

class m151220_112320_lang extends Migration
{
    public function safeUp()
    {
        $this->createTable('lang', [
            'id' => $this->string(2)->notNull(),
            'locale' => $this->string(8)->notNull(),
            'name' => $this->string(32)->notNull(),
            'status' => $this->smallInteger()
        ]);

        $this->addPrimaryKey('lang_pk', 'lang', 'id');

        $this->createIndex('lang_name_idx', 'lang', 'name', true);
        $this->createIndex('lang_status_idx', 'lang', 'status');

        $this->insert('lang', [
            'id' => 'en',
            'locale' => 'en-US',
            'name' => 'ENG',
            'status' => Lang::STATUS_ACTIVE,
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('lang');
    }
}