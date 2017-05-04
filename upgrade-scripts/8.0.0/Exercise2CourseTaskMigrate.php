<?php
// TODO
class Exercise2CourseTaskMigrate extends AbstractMigrate
{
    public function update($page)
    {
        if (!$this->isTableExist('exercise')) {
            return;
        }

        $this->migrateTableStructure();

        $count = $this->getConnection()->fetchColumn("
            SELECT count(*) FROM (select max(id) as id,lessonId from exercise group by lessonId) as tmp WHERE id NOT IN (SELECT migrateExerciseId FROM activity WHERE mediaType='exercise') AND `lessonId`  IN (SELECT id FROM `course_lesson`);
        ");

        if (empty($count)) {
            $this->updateExerciseActivity();
            $this->updateExerciseTask();
            $this->updateExerciseLessonId();
            return;
        }

        $this->exerciseToActivity();
        $this->exerciseToCourseTask();

        return $page + 1;
    }

    /**
     * exercise datas convert to  activity
     * TODO datas should read from table tespaper.
     */
    protected function exerciseToActivity()
    {
        $this->getConnection()->exec("
            INSERT INTO `activity`
            (
              `title`,
              `remark` ,
              `mediaId` ,
              `mediaType`,
              `content`,
              `length`,
              `fromCourseId`,
              `fromCourseSetId`,
              `fromUserId`,
              `startTime`,
              `endTime`,
              `createdTime`,
              `updatedTime`,
              `copyId`,
              `migrateExerciseId`,
              `migrateLessonId`
            )
            SELECT
              CONCAT(`title`,'的练习'),
              `summary`,
              `eexerciseId`,
              'exercise',
              `summary`,
              0,
              `courseId`,
              `courseId`,
              `userId`,
              `startTime`,
              `endTime`,
              `createdTime`,
              `updatedTime`,
              `ecopyId`,
              `eexerciseId`,
              `id`
            FROM (SELECT  max(ee.id) AS eexerciseId, max(ee.`copyId`) AS ecopyId , ce.*
                FROM  course_lesson  ce , exercise ee WHERE ce.id = ee.lessonId group by ee.lessonId limit 0, {$this->perPageCount}) lesson
            WHERE lesson.eexerciseId NOT IN (SELECT migrateExerciseId FROM activity WHERE migrateExerciseId IS NOT NULL );
        "
        );
    }

    protected function exerciseToCourseTask()
    {
        $this->getConnection()->exec(
            "INSERT INTO course_task
            (
              `courseId`,
              `fromCourseSetId`,
              `categoryId`,
              `seq`,
              `title`,
              `isFree`,
              `startTime`,
              `endTime`,
              `status`,
              `createdUserId`,
              `createdTime`,
              `updatedTime`,
              `mode` ,
              `number`,
              `type`,
              `length` ,
              `maxOnlineNum`,
              `copyId`,
              `migrateExerciseId`,
              `migrateLessonId`
            )
          SELECT
            `courseId`,
            `courseId`,
            `chapterId`,
            `seq`,
            CONCAT(`title`,'的练习'),
            0,
            `startTime`,
            `endTime`,
            `status`,
            `userId`,
            `createdTime`,
            `updatedTime`,
            'exercise',
            `number`,
            'exercise',
            0,
            `maxOnlineNum`,
            `copyId`,
            `eexerciseId`,
            `id`
            FROM (SELECT  max(ee.id) AS eexerciseId, ee.`copyId` AS ecopyId , ce.*
              FROM  course_lesson  ce , exercise ee WHERE ce.id = ee.lessonid group by ee.lessonId limit 0, {$this->perPageCount}) lesson
                  WHERE lesson.eexerciseId NOT IN (SELECT migrateExerciseId FROM course_task WHERE migrateExerciseId IS NOT NULL );
          "
        );
    }

    protected function updateExerciseTask()
    {
        $this->getConnection()->exec("
            UPDATE course_task as a, (SELECT id,migrateExerciseId from course_task where type = 'exercise') AS tmp set a.copyId = tmp.id WHERE tmp.migrateExerciseId = a.copyId AND a.type = 'exercise' AND a.copyId > 0;
        ");
    }

    protected function updateExerciseActivity()
    {
        $this->getConnection()->exec("
            UPDATE activity as a, (SELECT id,migrateExerciseId from activity where mediaType = 'exercise') AS tmp set a.copyId = tmp.id WHERE tmp.migrateExerciseId = a.copyId AND a.mediaType = 'exercise' AND a.copyId > 0;
        ");
    }

    protected function migrateTableStructure()
    {
        if (!$this->isFieldExist('activity', 'migrateExerciseId')) {
            $this->getConnection()->exec('alter table `activity` add `migrateExerciseId` int(10) ;');
        }

        if (!$this->isFieldExist('course_task', 'migrateExerciseId')) {
            $this->getConnection()->exec('alter table `course_task` add `migrateExerciseId` int(10) ;');
        }
    }

    protected function updateExerciseLessonId()
    {
        $sql = "UPDATE testpaper_v8 AS t, activity AS a SET t.lessonId = a.id WHERE t.lessonId = a.migrateLessonId AND t.type = 'exercise';";
        $this->getConnection()->exec($sql);
    }
}
