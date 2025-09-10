<?php

namespace Biz\Activity\Type;

use AppBundle\Common\ArrayToolkit;
use Biz\Activity\Config\Activity;
use Biz\Activity\Dao\DownloadActivityDao;
use Biz\File\Service\UploadFileService;

class Download extends Activity
{
    protected function registerListeners()
    {
        // TODO: Implement registerListeners() method.
    }

    /**
     * {@inheritdoc}
     */
    public function create($fields)
    {
        $files = json_decode($fields['materials'], true);
        $fileIds = array_keys($files);

        $downloadActivity = ['mediaCount' => count($files), 'fileIds' => $fileIds];
        $downloadActivity = $this->getDownloadActivityDao()->create($downloadActivity);

        return $downloadActivity;
    }

    public function copy($activity, $config = [])
    {
        $download = $this->getDownloadActivityDao()->get($activity['mediaId']);
        $newDownload = [
            'mediaCount' => $download['mediaCount'],
            'fileIds' => $download['fileIds'],
        ];

        return $this->getDownloadActivityDao()->create($newDownload);
    }

    public function sync($sourceActivity, $activity)
    {
        $sourceDownload = $this->getDownloadActivityDao()->get($sourceActivity['mediaId']);
        $download = $this->getDownloadActivityDao()->get($activity['mediaId']);
        $download['mediaCount'] = $sourceDownload['mediaCount'];
        $download['fileIds'] = $sourceDownload['fileIds'];

        return $this->getDownloadActivityDao()->update($download['id'], $download);
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, &$fields, $activity)
    {
        $files = json_decode($fields['materials'], true);

        $fileIds = array_keys(ArrayToolkit::index($files, 'fileId'));

        $downloadActivity = ['mediaCount' => count($files), 'fileIds' => $fileIds];
        $downloadActivity = $this->getDownloadActivityDao()->update($id, $downloadActivity);

        return $downloadActivity;
    }

    public function updateByIds($ids, $fields)
    {
        if (empty($ids)) {
            return;
        }
        $downloadActivities = $this->find($ids);
        $updates = [];
        foreach ($downloadActivities as $downloadActivity) {
            $downloadActivity['fileIds'][] = $fields['mediaId'];
            $updates[$downloadActivity['id']] = ['fileIds' => $downloadActivity['fileIds']];
        }
        $this->getDownloadActivityDao()->batchUpdate(array_keys($updates), $updates);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $download = $this->getDownloadActivityDao()->get($id);
        foreach ($download['fileIds'] as $fileId) {
            $this->getUploadFileService()->updateUsedCount($fileId);
        }

        return $this->getDownloadActivityDao()->delete($id);
    }

    /**
     * @return UploadFileService
     */
    protected function getUploadFileService()
    {
        return $this->getBiz()->service('File:UploadFileService');
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        return $this->getDownloadActivityDao()->get($id);
    }

    public function find($ids, $showCloud = 1)
    {
        return $this->getDownloadActivityDao()->findByIds($ids);
    }

    /**
     * @return DownloadActivityDao
     */
    public function getDownloadActivityDao()
    {
        return $this->getBiz()->dao('Activity:DownloadActivityDao');
    }

    public function materialSupported()
    {
        return true;
    }

    protected function getConnection()
    {
        return $this->getBiz()->offsetGet('db');
    }
}
