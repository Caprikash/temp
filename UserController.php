<?php

namespace frontend\controllers;

use frontend\models\UserModel;
use yii\web\Controller;
use frontend\models\Notepad;
use Yii;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\web\UploadedFile;

class UserController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['profile', 'notepad', 'vacancy', 'note-del', 'note-add', 'note-upd'],
                'rules' => [
                    [
                        'actions' => ['profile', 'notepad', 'vacancy', 'note-del', 'note-add', 'note-upd'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        $this->view->registerJsFile('/js/user.js');
        return parent::beforeAction($action);
    }

    /**
     * По умолчанию показать анкету менеджера.
     */
    public function actionIndex($id = null)
    {
        $model = new UserModel();

        if ($id) {
            $model->user_id = $id;
        } else {
            $model->user_id = Yii::$app->user->id;
        }

        $model->getUserInfo();
        if (!$model->userinfo['username']) {
            $this->goHome();
        }

        return $this->render('user', [
            'model' => $model
        ]);
    }

    /**
     * Редактировать свой профайл.
     */
    public function actionProfile()
    {
        $model = new UserModel();
        $model->scenario = 'base_info';
        $model->attributes = Yii::$app->user->identity->toArray();

        if (Yii::$app->request->isPost) {

            $model->scenario = 'user_upd';
            $model->afile = UploadedFile::getInstance($model, 'avatar');
            $model->pfile = UploadedFile::getInstance($model, 'photo');

            if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                if ($model->afile) {
                    if (!$model->uploadAvatar()) {
                        Yii::$app->session->setFlash('error', $model->getFirstError('afile'));
                    }
                }

                if ($model->pfile) {
                    if (!$model->uploadPhoto()) {
                        Yii::$app->session->setFlash('error', $model->getFirstError('pfile'));
                    }
                }

                if ($model->userUpd()) {
                    Yii::$app->session->setFlash('success', 'Данные обновлены');
                }
            }
        }

        return $this->render('profile', [
            'model' => $model
        ]);
    }

    /**
     * Показать блокнот менеджера.
     *
     * @return mixed
     */
    public function actionNotepad()
    {
        $model = new Notepad();
        $model->user_id = Yii::$app->user->id;
        $model->getNotes();

        return $this->render('notepad', [
            'model' => $model,
        ]);
    }

    /**
     * Показать вакансии.
     *
     * @return mixed
     */
    public function actionVacancy()
    {
        return $this->render('vacancy');
    }

    /**
     * AJAX
     */
    public function actionNoteDel()
    {
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $model = new Notepad();
            $model->scenario = 'note_del';
            if ($model->load(Yii::$app->request->post(), '') && $model->validate() && $model->noteDel()) {
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

    public function actionNoteAdd()
    {
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $model = new Notepad();
            $model->scenario = 'note_add';
            if ($model->load(Yii::$app->request->post(), '') && $model->validate() && $model->noteAdd()) {
                return [
                    'success' => true,
                    'insert_id' => $model->last_insert_id,
                    'note_text_formatted' => nl2br($model->note_text),
                ];
            }

            return [
                'success' => false,
                'errors' => $model->errors
            ];
        }
    }

    public function actionNoteUpd()
    {
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $model = new Notepad();
            $model->scenario = 'note_upd';
            if ($model->load(Yii::$app->request->post(), '') && $model->validate() && $model->noteUpd()) {
                return [
                    'success' => true,
                    'note_text_formatted' => nl2br($model->note_text),
                ];
            }

            return [
                'success' => false,
                'errors' => $model->errors
            ];
        }
    }

    public function actionUserDelAvatar()
    {
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $model = new UserModel();
            $model->user_id = Yii::$app->user->id;
            $model->scenario = 'user_del_file';

            if ($model->load(Yii::$app->request->post(), '') &&  $model->delAvatar()) {
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

    public function actionUserDelPhoto()
    {
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $model = new UserModel();
            $model->user_id = Yii::$app->user->id;
            $model->scenario = 'user_del_file';

            if ($model->load(Yii::$app->request->post(), '') &&  $model->delPhoto()) {
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
}
