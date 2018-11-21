<?php

class ProjectComponent extends CApplicationComponent
{
    private $_model = null;

    public function setModel($id)
    {
        $this->_model = Project::model()->findByPk($id);
    }

    public function isTeamMember($id=null)
    {
        $projectId = is_null($id) ? $this->id : $id;
        $model = ProjectTeam::model()->findByAttributes(array(
            'project_id' => $projectId,
            'user_id'    => Yii::app()->user->id
        ));

        return isset($model);
    }

    public function getModel()
    {
        $projectId = Yii::app()->session['__project_id'];

        if (null !== $this->_model) {
            if (isset($projectId)) {
                $this->_model = Project::model()->findByPk($projectId);
            }
        }

        return $this->_model;
    }

    public function getId()
    {
        return $this->model->id;
    }

    public function getName()
    {
        return $this->model->name;
    }
}
