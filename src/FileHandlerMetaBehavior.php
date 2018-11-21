<?php

class FileHandlerMetaBehavior extends CBehavior
{
    public function setMeta($key, $value, $type='create')
    {
        if (!isset($value) || empty($value)) {
            return false;
        }

        $meta = ProposalMeta::model()->find(array(
            'condition'=>'meta_key=:key AND proposal_id=:id',
            'params'=>array(':id'=>$this->owner->id, ':key'=>$key),
        ));
        if (!$meta instanceof ProposalMeta) {
            $meta = new ProposalMeta('create');
            $meta->proposal_id = $this->owner->id;
            $meta->meta_key = $key;
        }
        $meta->meta_value  = is_array($value) || is_object($value) ? json_encode($value) : $value;
        $meta->order = 1;

        return $meta->save();
    }

    public function getMeta($key, $default=null)
    {
        $meta = ProposalMeta::model()->find(array(
            'condition'=>'meta_key=:key AND proposal_id=:id',
            'params'=>array(':id'=>$this->owner->id, ':key'=>$key),
        ));

        if ($meta === null) {
            return $default;
        }
        $value = json_decode($meta->meta_value, true);

        if (!isset($value) && !empty($meta->meta_value)) {
            return $meta->meta_value;
        } elseif (isset($value)) {
            return $value;
        } else {
            return $default;
        }
    }

    public function saveAttachment($cuploaded, $filename, $location, $order)
    {
        return $this->saveFiles(ProposalFile::TYPE_ATTACHMENT, $cuploaded, $filename, $location, $order);
    }

    public function saveDocument($cuploaded, $filename, $location, $order)
    {
        return $this->saveFiles(ProposalFile::TYPE_DOCUMENT, $cuploaded, $filename, $location, $order);
    }

    public function getAttachments()
    {
        return $this->retrieveFiles(ProposalFile::TYPE_ATTACHMENT);
    }

    public function getDocuments()
    {
        return $this->retrieveFiles(ProposalFile::TYPE_DOCUMENT);
    }

    protected function saveFiles($fileType, $cuploaded, $filename, $location, $order)
    {
        // Save to File Model
        $file = new File('create');
        $file->file_name = $cuploaded->name;
        $file->ext       = $cuploaded->extensionName;
        $file->location  = "$location/$filename";
        $file->metadata  = json_encode(array(
            'error'=>$cuploaded->error,
        ));
        $file->mime_type = $cuploaded->type;
        $file->byte_size = $cuploaded->size;
        $file->created_by = Yii::app()->user->id;
        $file->created_stamp = new CDbExpression('NOW()');

        /*if (!$file->save()) {
           var_dump($cuploaded);
        }

        // var_dump($file->errors);
        exit;*/

        if ($file->save()) {
            $proposalFile = new ProposalFile('create');
            $proposalFile->proposal_id = $this->owner->id;
            $proposalFile->file_id     = $file->id;
            $proposalFile->file_type   = $fileType;

            return $proposalFile->save();
        } else {
            return false;
        }
    }

    protected function retrieveFiles($fileType)
    {
        if (null === $this->owner->id) {
            return array();
        }

        $criteria = new CDbCriteria;
        $criteria->compare('proposal_id', $this->owner->id);
        $criteria->compare('file_type', $fileType);
        $proFiles = ProposalFile::model()->findAll($criteria);

        $files = array();
        if (count($proFiles)>0) {
            $files = array_map(function ($proFile) {
                return $proFile->file;
            }, $proFiles);
        }

        return $files;
    }
}
