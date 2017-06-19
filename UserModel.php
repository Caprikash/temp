<?php

namespace frontend\models;

use yii;
use yii\base\Model;
use common\models\User;
use yii\imagine\Image;
use Imagine\Gd;
use Imagine\Image\Box;
use yii\helpers\BaseFileHelper;

class UserModel extends Model
{

    public $user_id;
    public $username;
    public $email;
    public $first_name;
    public $last_name;
    public $city;
    public $birthday;
    public $avatar;
    public $photo;
    public $afile;
    public $pfile;

    public function __construct()
    {
        $this->user_id = Yii::$app->user->id;
        parent::__construct();
    }
    
    public function rules()
    {
        return [
            [['first_name', 'city', 'birthday', 'last_name'], 'string'],
            ['user_id', 'exist', 'targetClass' => User::className(), 'targetAttribute' => 'id'],
            [['afile'], 'image', 'skipOnEmpty' => true, 'extensions' => 'png, jpg'],
            [['pfile'], 'image', 'skipOnEmpty' => true, 'extensions' => 'png, jpg']
        ];
    }

    public function scenarios()
    {
        return [
            'user_upd' => ['user_id', 'first_name', 'last_name', 'city', 'afile', 'pfile', 'birthday'],
            'user_del_file' => ['user_id'],
            'base_info' => ['user_id', 'username', 'email', 'first_name', 'last_name', 'city', 'birthday', 'avatar', 'photo']
        ];
    }

    /**
     * Получить данные о пользователе
     */
    public function getUserInfo()
    {
        return User::findOne($this->user_id);
    }

    /**
     * Обновить данные о пользователе
     */
    public function userUpd()
    {
        $user = User::findOne($this->user_id);
        $user->first_name = $this->first_name;
        $user->last_name = $this->last_name;
        $user->city = $this->city;
        $user->birthday = date("Y-m-d", strtotime($this->birthday));
  
        if ($this->avatar != '') {
            $user->avatar = $this->avatar;
        }

        if ($this->photo != '') {
            $user->photo = $this->photo;
        }

        return $user->save();
    }

    /**
     * Загрузка и ресайз аватарки
     */
    public function uploadAvatar()
    {
        $this->delAvatarFiles();

        BaseFileHelper::createDirectory('images/avatars/' . $this->user_id, 755);

        $avatar_name = time() . rand(0,9999);
        $this->avatar = $avatar_name . '.' . $this->afile->extension;

        if ($this->afile->saveAs('images/avatars/' . $this->user_id . '/' . $this->avatar)) {
            $path = Yii::$app->basePath . '/web/images/avatars/' . $this->user_id . '/';
            Image::getImagine()->open($path . $this->avatar)
                ->thumbnail(new Box(16, 16))
                ->save($path . '16_' . $this->avatar, ['quality' => 90]);
            Image::getImagine()->open($path . $this->avatar)
                ->thumbnail(new Box(32, 32))
                ->save($path . '32_' . $this->avatar, ['quality' => 90]);
            Image::getImagine()->open($path . $this->avatar)
                ->thumbnail(new Box(64, 64))
                ->save($path . '64_' . $this->avatar, ['quality' => 90]);
        }

        return true;
    }

    /**
     * Загрузка и ресайз фото
     */
    public function uploadPhoto()
    {
        $this->delPhotoFiles();

        BaseFileHelper::createDirectory('images/photos/' . $this->user_id, 777);

        $this->photo = time() . rand(0,9999) . '.' . $this->pfile->extension;

        if ($this->pfile->saveAs('images/photos/' . $this->user_id . '/' . $this->photo)) {
            $path = Yii::$app->basePath . '/web/images/photos/' . $this->user_id . '/';
            Image::getImagine()->open($path . $this->photo)
                ->thumbnail(new Box(800, 640))
                ->save($path . $this->photo, ['quality' => 90]);
        }

        return true;
    }

    /**
     * Удаление аватарки
     */
    public function delAvatar()
    {
        $this->delAvatarFiles();
        $user = User::findIdentity($this->user_id);
        $user->avatar = null;
        return $user->save();
    }

    public function delAvatarFiles()
    {
        $path = Yii::$app->basePath . '/web/images/avatars/' . $this->user_id . '/';

        if (is_file($path . $this->avatar)) {
            unlink($path . $this->avatar);
        }

        if (is_file($path . '16_' . $this->avatar)) {
            unlink($path . '16_' . $this->avatar);
        }

        if (is_file($path . '32_' . $this->avatar)) {
            unlink($path . '32_' . $this->avatar);
        }

        if (is_file($path . '64_' . $this->avatar)) {
            unlink($path . '64_' . $this->avatar);
        }


        return true;
    }

    /**
     * Удаление фото
     */
    public function delPhoto()
    {
        $this->delPhotoFiles();
        $user = User::findIdentity($this->user_id);
        $user->photo = null;
        return $user->save();
    }

    public function delPhotoFiles()
    {
        $path = Yii::$app->basePath . '/web/images/photos/' . $this->user_id . '/';
        if (is_file($path . $this->photo)) {
            unlink($path . $this->photo);
        }

        return true;
    }
}