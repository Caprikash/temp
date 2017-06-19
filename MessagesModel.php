<?php

namespace frontend\models;

use yii;
use yii\base\Model;
use common\models\User;
use frontend\models\dbmodels\Dialogs;
use frontend\models\dbmodels\DialogRecipients;
use frontend\models\dbmodels\Messages;

class MessagesModel extends Model
{

    public $last_id;
    public $user_id;
    public $managers;
    public $manager_id;
    public $manager_login;
    public $dialog_id;
    public $dialog;
    public $dialogs;
    public $messages;
    public $managers_list;
    public $message_text;
    public $my_username;

    public function __construct()
    {
        $this->user_id = Yii::$app->user->id;
        $this->my_username = Yii::$app->user->identity['username'];
        parent::__construct();
    }
    
    public function rules()
    {
        return [
            [['manager_id'], 'integer'],
            [['managers_list'], 'required'],
            [['dialog_id'], 'integer'],
            [['manager_login'], 'string'],
            [['message_text'], 'required'],
            ['user_id', 'exist', 'targetClass' => User::className(), 'targetAttribute' => 'id']
        ];
    }

    public function scenarios()
    {
        return [
            'change_managers' => ['managers_list', 'dialog_id'],
            'clear_notice' => ['dialog_id'],
            'managers_find' => ['manager_login'],
            'manager_add' => ['manager_id', 'dialog_id'],
            'manager_del' => ['manager_id', 'dialog_id'],
            'dialog_add' => ['managers_list', 'message_text'],
            'message_add' => ['user_id', 'message_text', 'dialog_id'],
            'dialog_view' => ['dialog_id']
        ];
    }

    /**
     * Все диалоги менеджера
     */
    public function getDialogs()
    {
        $this->dialogs = array_merge(Dialogs::findAllByUser($this->user_id), DialogRecipients::findAllByUser($this->user_id));
        
        foreach ($this->dialogs as $key => $dlg) {
            $this->dialogs[$key]['managers'] = array_merge(
                Dialogs::findAllByDialog($dlg['dialog_id']),
                DialogRecipients::findAllByDialog($dlg['dialog_id'])
            );
        }
        return $this->dialogs;
    }

    /**
     * Проверка на участие в диалоге
     */
    public function isMyDialog($id)
    {
        if (Dialogs::findMyDialog($id, $this->user_id) || DialogRecipients::findMyDialog($id, $this->user_id)) {
            return true;
        }
        return false;
    }

    /**
     * Проверка на участие в диалоге
     */
    public function isDialogOpened()
    {
        $w1 = Dialogs::findManagersDialog($this->user_id, $this->manager_id);
        $w2 = Dialogs::findManagersDialog($this->manager_id, $this->user_id);

        if ($w1) {
            $this->dialog_id = $w1["dialog_id"];
        }

        if ($w2) {
            $this->dialog_id = $w2["dialog_id"];
        }
        
        return true;
    }

    /**
     * Проверка на создателя разговора
     */
    public function isOwnerDialog()
    {
        $this->dialog_id = Dialogs::findMyDialog($this->dialog_id, $this->user_id);
        return $this->dialog_id;
    }


    /**
     * Загрузить диалог
     */
    public function getDialog()
    {
        $this->dialog = array_merge(Dialogs::findAllByDialog($this->dialog_id), DialogRecipients::findAllByDialog($this->dialog_id));

        return $this->dialog;
    }

    /**
     * Загрузить диалог
     */
    public function getDialogRecipients()
    {
        return DialogRecipients::findAllByDialog($this->dialog_id);
    }

    /**
     * Загрузить диалог
     */
    public function getMessages()
    {
        $this->messages = Messages::findAllMessagesByDialog($this->dialog_id);
        return $this->messages;
    }

    /**
     * Cоздать разговор
     */
    public function dialogAdd()
    {
        $dialog = new Dialogs();
        $dialog->user_id = $this->user_id;
        $dialog->save();

        $dialog_res = new DialogRecipients();
        $dialog_res->dialog_id = $dialog->dialog_id;
        $dialog_res->user_id = $this->managers_list;
        $dialog_res->save();

        $this->last_id = $dialog->dialog_id;
        $this->dialog_id = $dialog->dialog_id;

        $this->messageAdd();

        return $this->last_id;
    }

    /**
     * Добавить сообщение
     */
    public function messageAdd()
    {

        $messages = new Messages();
        $messages->dialog_id = $this->dialog_id;
        $messages->user_id = $this->user_id;
        $messages->message_text = $this->message_text;
        $messages->post_date = new yii\db\Expression('NOW()');
        $messages->save();

        $w1 = Dialogs::findAllByDialog($this->dialog_id, $this->user_id);
        $w2 = DialogRecipients::findAllByDialog($this->dialog_id,$this->user_id);
        $notice_users = array_merge($w1, $w2);
        foreach ($notice_users as $nu) {
            $usr = new UserModel();
            $usr->user_id = $nu['user_id'];
            $userinfo = $usr->getUserInfo();
            if (is_array(unserialize($userinfo->dialogs))) {
                $dialogsarr = unserialize($userinfo->dialogs);
                $dialogsarr[$this->dialog_id]++;
            } else {
                $dialogsarr = [];
                $dialogsarr[$this->dialog_id] = 1;
            }
            $userinfo->dialogs = serialize($dialogsarr);
            $userinfo->save();
        }

        return $this->dialog_id;
    }

    /**
     * Обнулить уведомления
     */
    public function clearNotice()
    {
        $usr = new UserModel();
        $usr->user_id = $this->user_id;
        $userinfo = $usr->getUserInfo();
        $dialogsarr = unserialize($userinfo->dialogs);
        $dialogsarr[$this->dialog_id] = 0;
        $userinfo->dialogs = serialize($dialogsarr);
        $userinfo->save();

        return $this->dialog_id;
    }

    /**
     * Удалить менеджера
     */
    public function managerDel()
    {
        return DialogRecipients::deleteAll(["dialog_id" => $this->dialog_id, "user_id" => $this->manager_id]);
    }

    /**
     * Добавить менеджера
     */
    public function managerAdd()
    {
        $dialog_res = new DialogRecipients();
        $dialog_res->dialog_id = $this->dialog_id;
        $dialog_res->user_id = $this->manager_id;
        $dialog_res->save();
        return true;
    }

    /**
     * Найти менеджеров
     */
    public function managersFind()
    {
        $this->managers = User::find()
            ->andFilterWhere(['like', 'username', $this->manager_login])
            ->andFilterWhere(['!=', 'username', $this->my_username])
            ->all();
        return $this->managers;
    }


    /**
     * Новый список собеседников
     */
    public function changeManagers()
    {
        DialogRecipients::deleteAll('dialog_id = ' . $this->dialog_id);

        $recip = $this->managers_list;

        foreach ($recip as $i => $r) {
            $dialog_res = new DialogRecipients();
            $dialog_res->dialog_id = $this->dialog_id;
            $dialog_res->user_id = $i;
            $dialog_res->save();
        }

        return $this->managers;
    }

}