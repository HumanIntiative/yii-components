<?php

class TaskQueueBehavior extends CBehavior
{
    /**
     * Queue ParseMpp Task
     * @param  ProjectFile $model
     * @return boolean
     */
    public function queueParseMppTask($model)
    {
        $queue = new TaskQueue('create');
        $queue->task_name     = 'Parse Mpp File';
        $queue->task_type     = TaskQueueTypeEnum::PARSE_MPP;
        $queue->mapclass_name = get_class($model);
        $queue->mapclass_id   = $model->primaryKey;
        $queue->date_created  = new CDbExpression('NOW()');
        $queue->param_data    = json_encode(array(
            'project_id'    => $model->project_id,
            'file_id'       => $model->file_id,
            'file_name'     => $model->file ? $model->file->file_name : null,
            'file_ext'      => $model->file ? $model->file->ext : null,
            'file_path'     => $model->project ? $model->project->fileMppUploadPath : null,
            'file_location' => $model->file ? $model->file->location : null,
            'file_size'     => $model->file ? $model->file->byte_size : null,
        ));

        return $queue->save();
    }
}
