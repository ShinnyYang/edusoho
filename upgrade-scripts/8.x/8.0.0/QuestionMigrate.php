<?php

class QuestionMigrate extends AbstractMigrate
{
    public function update($page)
    {
        if (!$this->isFieldExist('question', 'courseId')) {
            $this->getConnection()->exec("
                ALTER TABLE question add courseId INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `target`
            ");
        }

        if (!$this->isFieldExist('question', 'courseSetId')) {
            $this->getConnection()->exec("
                ALTER TABLE `question` ADD COLUMN `courseSetId` INT(10) NOT NULL DEFAULT '0'  AFTER `target`
            ");
        }

        if (!$this->isFieldExist('question', 'lessonId')) {
            $this->getConnection()->exec("
                ALTER TABLE question add lessonId INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `courseId`
            ");
        }

        $nextPage = $this->updateQuestion($page);
        if (!empty($nextPage)) {
            return $nextPage;
        }
    }

    private function updateQuestion($page)
    {
        $sql = "SELECT * FROM question where courseSetId = 0 AND target != '' ORDER BY id LIMIT 0, {$this->perPageCount};";

        $questions = $this->getConnection()->fetchAll($sql);

        if (empty($questions)) {
            return;
        }

        foreach ($questions as $question) {
            $targetArr = explode('/', $question['target']);
            $courseArr = explode('-', $targetArr[0]);
            $courseId = 0;
            $lessonId = 0;
            if (!empty($targetArr[1])) {
                $courseId = intval($courseArr[1]);
                $lessonArr = explode('-', $targetArr[1]);
                $lessonId = intval($lessonArr[1]);
            }

            $sql = "UPDATE question set courseId = {$courseId},courseSetId = {$courseArr[1]},lessonId={$lessonId} WHERE id = {$question['id']}";
            $this->getConnection()->exec($sql);
        }

        return $page + 1;
    }
}
