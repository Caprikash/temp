<?php

namespace frontend\controllers;
use yii\web\Controller;
use frontend\models\MessagesModel;
use Yii;
use yii\web\Response;
use yii\filters\AccessControl;
use frontend\models\UserModel;
use yii\helpers\Url;

class MessagesController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['add', 'dialog-add', 'ignor', 'dialog-view', 'managers-find', 'manager-add', 'manager-del', 'change-managers'],
                'rules' => [
                    [
                        'actions' => ['add', 'dialog-add', 'ignor', 'dialog-view', 'managers-find', 'manager-add', 'manager-del', 'change-managers'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        $this->view->registerJsFile('/js/messages.js');
        return parent::beforeAction($action);
    }

    /**
     * По умолчанию показать список диалогов.
     */
    public function actionIndex($id = null, $uid = null)
    {

        $model = new MessagesModel();
        $model->getDialogs();
        if ($uid) {
            $model->manager_id = $uid;
            $model->isDialogOpened();
            if ($model->dialog_id) {
                $id = $model->dialog_id;
            }
        }

        if ($uid && !$model->dialog_id) {
            $usr = new UserModel();
            $usr->user_id = $uid;
            $userinfo = $usr->getUserInfo();


            return $this->render('addDialogForm', [
                'model' => $model,
                'recipient' => $userinfo
            ]);
        }

        if ($id && $model->isMyDialog($id)) {
            $model->dialog_id = $id;
            $model->getMessages();
            $model->getDialog();
        }

        return $this->render('messages', [
            'model' => $model,
            'owner' => $model->isOwnerDialog()
        ]);
    }

    /**
     * Редактировать игнор список
     */
    public function actionIgnor()
    {
        return $this->render('messages');
    }

    /**
     * Показать форму создания разговора.
     *
     * @return mixed
     */
    public function actionAdd()
    {
        $model = new MessagesModel();

        return $this->render('addDialogForm', [
            'model' => $model,
        ]);
    }

    /**
     * AJAX
     */


    public function actionDialogAdd()
    {

        if (Yii::$app->request->isAjax) {

            Yii::$app->response->format = Response::FORMAT_JSON;

            $model = new MessagesModel();

            $model->scenario = 'dialog_add';

            if ($model->load(Yii::$app->request->post(), '') && $model->validate() && $model->dialogAdd()) {

                return [
                    'success' => true,
                    'redirectUrl' => Url::toRoute(['/messages', 'id' => $model->last_id])
                ];
            }

            return [
                'success' => false,
                'errors' => $model->errors
            ];
        }
    }

    public function actionMessageAdd()
    {

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $model = new MessagesModel();

            $model->scenario = 'message_add';

            if ($model->load(Yii::$app->request->post(), '') && $model->validate() && $model->messageAdd()) {

                return [
                    'success' => true,
                ];
            }

            return [
                'success' => false,
                'errors' => $model->errors
            ];
        }
    }

    public function actionManagerAdd()
    {
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $model = new MessagesModel();
            $model->scenario = 'manager_add';
            if ($model->load(Yii::$app->request->post(), '') && $model->validate() && $model->managerAdd()) {
                return [
                    'success' => true
                ];
            }

            return [
                'success' => false,
                'errors' => $model->errors
            ];
        }
    }

    public function actionManagerDel()
    {
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $model = new MessagesModel();
            $model->scenario = 'manager_del';
            if ($model->load(Yii::$app->request->post(), '') && $model->validate() && $model->isOwnerDialog()) {
                $model->managerDel();
                return [
                    'success' => true,
                ];
            }

            return [
                'success' => false,
                'errors' => $model->errors
            ];
        }
    }

    public function actionNewMessagesGet()
    {

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $model = new MessagesModel();
            $usr = new UserModel();
            $usr->user_id = $model->user_id;
            $userinfo = $usr->getUserInfo();
            return [
                'success' => true,
                'updated' => unserialize($userinfo->dialogs)
            ];

        }
    }

    public function actionManagersFind()
    {
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $model = new MessagesModel();
            $model->scenario = 'managers_find';
            if ($model->load(Yii::$app->request->post(), '') && $model->validate() && $model->managersFind()) {
                return [
                    'success' => true,
                    'managers' => $model->managers
                ];
            }

            return [
                'success' => false,
                'errors' => $model->errors
            ];
        }
    }

    public function actionDialogView()
    {
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $model = new MessagesModel();
            $model->scenario = 'dialog_view';
            $model->load(Yii::$app->request->post(), '');


            if ($model->isMyDialog($model->dialog_id) && $model->validate() && $model->getMessages() && $model->getDialog()) {
                return [
                    'html' => $this->renderPartial('_dialog_messages', ['model' => $model]),
                    'owner' => $model->isOwnerDialog(),
                    'model' => $model->getDialogRecipients()
                ];

            }
        }
    }

    public function actionClearNotice()
    {
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $model = new MessagesModel();
            $model->scenario = 'clear_notice';
            $model->load(Yii::$app->request->post(), '');


            if ($model->validate() && $model->clearNotice()) {
                return [
                    'success' => true,
                ];
            }
        }
    }

    public function actionChangeManagers()
    {
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new MessagesModel();
            $model->scenario = 'change_managers';

            if ($model->load(Yii::$app->request->post(), '') && $model->validate() && $model->changeManagers() && $model->isOwnerDialog()) {

                return [
                    'success' => true,
                ];
            }
        }
    }
}
