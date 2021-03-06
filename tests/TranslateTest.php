<?php

namespace tests;

use Yii;
use yii\db\ActiveQuery;

use tests\models\Post;

/**
 * Class TranslateTest
 * @package tests
 */
class TranslateTest extends DatabaseTestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        Yii::$app->language = Yii::$app->sourceLanguage;
        Yii::$container->set('lav45\translate\TranslatedBehavior', []);
    }

    public function testFindPosts()
    {
        Yii::$container->set('lav45\translate\TranslatedBehavior', [
            'language' => 'ru'
        ]);

        /** @var Post[] $posts */
        $posts = Post::find()
            ->with(['currentTranslate' => function (ActiveQuery $q) {
                $q->asArray();
            }])
            ->all();

        foreach ($posts as $key => $post) {
            $posts[$key] = $post->toArray();
        }

        $data = [
            0 => [
                'id' => 1,
                'title' => 'заголовок первой страницы',
                'description' => 'описание первого поста',
                'status_id' => 1,
            ],
            1 => [
                'id' => 2,
                'title' => 'title of the second post',
                'description' => 'description of the second post',
                'status_id' => 2,
            ],
        ];

        $this->assertEquals($data, $posts);
    }

    public function testCreatePost()
    {
        $model = new Post([
            'titleLang' => 'test for the create new post',
            'description' => 'description for the create new post',
            'status_id' => 1
        ]);

        $this->assertTrue($model->save(false));

        $dataSet = $this->getConnection()->createDataSet(['post', 'post_lang']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/testCreatePost.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testDeletePost()
    {
        /** @var Post $model */
        $model = Post::findOne(1);
        $model->delete();

        $dataSet = $this->getConnection()->createDataSet(['post', 'post_lang']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/testDeletePost.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testEditPost()
    {
        /** @var Post $model */
        $model = Post::findOne(1);
        $model->titleLang = 'new title';

        $this->assertTrue($model->save(false));

        $dataSet = $this->getConnection()->createDataSet(['post_lang']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/testEditPost.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testEditTranslate()
    {
        /** @var Post $model */
        $model = Post::findOne(1);
        $this->assertTrue($model->isSourceLanguage());
        $model->language = 'ru';
        $this->assertFalse($model->isSourceLanguage());
        $model->titleLang = 'new title';

        $this->assertTrue($model->save(false));

        $dataSet = $this->getConnection()->createDataSet(['post_lang']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/testEditTranslate.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testAddTranslate()
    {
        /** @var Post $model */
        $model = Post::findOne(2);
        $this->assertFalse($model->isTranslated());

        $this->assertTrue($model->getTranslation('ru')->save());
        $this->assertFalse($model->isTranslated());

        $model->language = 'ru';
        $this->assertTrue($model->isTranslated());
    }

    public function testHasTranslateRelations()
    {
        /** @var Post $model */
        $model = Post::findOne(1);
        $data = array_keys($model->hasTranslate);
        $expectedData = ['en', 'ru'];
        $this->assertEquals($data, $expectedData);

        $this->assertTrue($model->hasTranslate('en'));
        $this->assertFalse($model->hasTranslate('fr'));
    }

    public function testCurrentTranslateRelations()
    {
        Yii::$app->language = 'ru-RU';

        /** @var Post $model */
        $model = Post::findOne(1);
        $this->assertTrue(count($model->currentTranslate) == 2);

        $data = array_keys($model->currentTranslate);
        $expectedData = ['en', 'ru'];
        $this->assertEquals($data, $expectedData);
        $this->assertTrue($model->hasTranslate('ru'));

        Yii::$app->language = 'fr-FR';

        /** @var Post $model */
        $model = Post::findOne(1);
        $this->assertTrue(count($model->currentTranslate) == 1);

        $data = array_keys($model->currentTranslate);
        $expectedData = ['en'];
        $this->assertEquals($data, $expectedData);
        $this->assertFalse($model->hasTranslate('fr'));
    }

    public function testLoadTranslateWithoutCurrentTranslate()
    {
        /** @var Post $model */
        $model = Post::find()
            ->with(['postLangs'])
            ->where(['id' => 1])
            ->one();

        $this->assertEquals(['postLangs'], array_keys($model->getRelatedRecords()));
        $this->assertEquals($model->titleLang, 'title of the first post');
        $this->assertEquals(['postLangs'], array_keys($model->getRelatedRecords()));

        /** @var Post $model */
        $model = Post::find()
            ->where(['id' => 1])
            ->one();

        $this->assertEquals($model->titleLang, 'title of the first post');
        $this->assertEquals(['currentTranslate', 'postLangs'], array_keys($model->getRelatedRecords()));

        /** @var Post $model */
        $model = Post::find()
            ->with(['currentTranslate'])
            ->where(['id' => 1])
            ->one();

        $this->assertEquals(['currentTranslate'], array_keys($model->getRelatedRecords()));
        $this->assertEquals($model->titleLang, 'title of the first post');
        $this->assertEquals(['currentTranslate', 'postLangs'], array_keys($model->getRelatedRecords()));
    }

    public function testCallTranslateMethod()
    {
        $model = new Post;

        $this->assertEquals($model->testMethod(), 'OK');
        $this->assertEquals($model->testProperty, 'OK');

        $expectedData = uniqid();
        $model->testProperty = $expectedData;
        $this->assertEquals($model->testProperty, $expectedData);

        $expectedData = uniqid();
        $model->data = $expectedData;
        $this->assertEquals($model->data, $expectedData);

        $this->assertEquals($model->modelTestMethod(), 'OK');
        $this->assertEquals($model->modelTestProperty, 'OK');

        $expectedData = uniqid();
        $model->modelTestProperty = $expectedData;
        $this->assertEquals($model->modelTestProperty, $expectedData);

        $expectedData = uniqid();
        $model->modelData = $expectedData;
        $this->assertEquals($model->modelData, $expectedData);
    }

    public function testCheckSettings()
    {
        $model = new Post([
            'translateAttributes' => [
                'title',
                'description',
            ]
        ]);

        $this->assertEquals($model->translateAttributes, [
            'title' => 'title',
            'description' => 'description',
        ]);

        $model->translateAttributes = ['customTitle' => 'title'];

        $this->assertEquals($model->translateAttributes, [
            'customTitle' => 'title',
        ]);
    }

    public function testCheckIsSet()
    {
        $model = new Post;

        $this->assertFalse(isset($model->id));
        $this->assertFalse(isset($model->titleLang));
        $this->assertFalse(isset($model->description));

        $this->assertTrue(isset($model->lang_id));
        $this->assertEquals($model->lang_id, $model->getSourceLanguage());

        $this->assertTrue(isset($model->language));
        $this->assertTrue(isset($model->translation));
        $this->assertTrue(isset($model->currentTranslate));
        $this->assertTrue(isset($model->hasTranslate));
        $this->assertTrue(isset($model->modelTestProperty));

        /** @var Post $model */
        $model = Post::findOne(1);

        $this->assertTrue(isset($model->id));
        $this->assertTrue(isset($model->titleLang));
        $this->assertTrue(isset($model->description));
        $this->assertFalse(isset($model->title));

        $this->assertFalse(isset($model->modelData));
        $model->modelData = 'OK';
        $this->assertTrue(isset($model->modelData));

        $this->assertFalse(isset($model->data));
        $model->data = 'OK';
        $this->assertTrue(isset($model->data));
    }

    public function testTranslateAttributeName()
    {
        $model = new Post;
        $this->assertEquals($model->getTranslateAttributeName('titleLang'), 'title');
        $this->assertEquals($model->getTranslateAttributeName('description'), 'description');
        $this->assertEquals($model->getTranslateAttributeName('fff'), null);
    }

    public function testAttributeChanged()
    {
        /** @var Post $model */
        $model = Post::findOne(1);

        $this->assertFalse($model->isAttributeChanged('titleLang'));
        $this->assertFalse($model->isAttributeChanged('status_id'));

        $model->titleLang = 'test';
        $this->assertTrue($model->isAttributeChanged('titleLang'));

        $model->status_id = 2;
        $this->assertTrue($model->isAttributeChanged('status_id'));
    }

    public function testJoinCurrentTranslateRelation()
    {
        Yii::$app->language = 'ru-RU';

        $sql = Post::find()
            ->joinWith([
                'currentTranslate',
                'status.currentTranslate',
            ], false)
            ->createCommand()
            ->getRawSql();

        $this->assertEquals($sql,"SELECT `post`.* FROM `post` LEFT JOIN `post_lang` ON (`post`.`id` = `post_lang`.`post_id`) AND (`post_lang`.`lang_id` IN ('ru', 'en')) LEFT JOIN `status` ON `post`.`status_id` = `status`.`id` LEFT JOIN `status_lang` ON (`status`.`id` = `status_lang`.`status_id`) AND (`status_lang`.`lang_id` IN ('ru', 'en'))");
    }

    public function testCustomPrimaryLanguage()
    {
        Yii::$container->set('lav45\translate\TranslatedBehavior', [
            'primaryLanguage' => function($locale) {
                return strtolower($locale);
            }
        ]);

        $model = new Post;
        $model->status_id = 1;
        $model->title = 'title of the post US';
        $model->description = 'description of the post US';
        $model->titleLang = 'title of the post US';
        $model->save(false);

        $this->assertEquals('en-us', $model->lang_id);

        $model->language = 'en-GB';
        $model->titleLang = 'title of the post GB';
        $model->save(false);

        $this->assertEquals('en-gb', $model->lang_id);

        /** @var Post $data */
        $data = Post::findOne($model->id);
        $this->assertEquals('title of the post US', $data->titleLang);

        Yii::$app->language = 'en-GB';
        /** @var Post $data */
        $data = Post::findOne($model->id);
        $this->assertEquals('title of the post GB', $data->titleLang);
    }
}