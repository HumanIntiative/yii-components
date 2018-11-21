<?php

class ProjectReportComment extends CModel
{
    public $id;
    public $report_id;
    public $content;
    public $created_by;
    public $created_stamp;

    public function __construct($scenario='insert')
    {
        $this->setScenario($scenario);
        $this->attachBehaviors($this->behaviors());
        $this->afterConstruct();
    }

    public function attributeNames()
    {
        return array('id', 'report_id', 'content', 'created_by', 'created_stamp');
    }

    public function rules()
    {
        return array(
            array('report_id, content, created_by, created_stamp', 'required'),
        );
    }

    public function save($model, $runValidation=true, $attributes=null)
    {
        if (!in_array(get_class($model), array('ProjectQuickReport', 'ProjectGeneralReport', 'ProjectPost'))) {
            throw new CDbException("Model is instance of ".get_class($model));
        }

        if (!$model->hasAttribute('comments')) {
            throw new CDbException(get_class($model)." tidak memiliki comments column.");
        }

        if ($model->id != $this->report_id) {
            throw new CDbException("Report ID tidak sesuai");
        }
            

        if (!$runValidation || $this->validate($attributes)) {
            return $this->insert($model);
        } else {
            return false;
        }
    }

    protected function insert($model)
    {
        $comment = new stdClass;
        $comment->content       = $this->content;
        $comment->created_by    = $this->created_by;
        $comment->created_stamp = $this->created_stamp;

        $data = json_decode($model->comments);
        if (is_bool($data) || is_null($data)) {
            $data = array();
        }
        array_push($data, $comment);

        $model->comments = json_encode($data);
        return $model->save();
    }
}
