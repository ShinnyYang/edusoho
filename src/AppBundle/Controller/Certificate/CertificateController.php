<?php

namespace AppBundle\Controller\Certificate;

use AppBundle\Controller\BaseController;
use Biz\Certificate\CertificateException;
use Biz\Certificate\Service\CertificateService;
use Biz\Certificate\Service\RecordService;
use Biz\Classroom\ClassroomException;
use Biz\Classroom\Service\ClassroomService;
use Biz\Course\CourseException;
use Biz\Course\Service\CourseService;
use Biz\User\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CertificateController extends BaseController
{
    public function generateImageAction(Request $request, $id)
    {
        $record = $this->getRecordService()->get($id);
        if (empty($record)) {
            return $this->createJsonResponse('');
        }

        $img = $this->getCertificateStrategy($record['targetType'])->getCertificateImg($record);

        return $this->createJsonResponse($img);
    }

    public function certificateRecordAction(Request $request, $recordId)
    {
        $record = $this->getRecordService()->getByCode($recordId);
        if (empty($record) || in_array($record['status'], ['reject', 'none', 'cancelled'])) {
            return $this->createMessageResponse('info', '证书不存在', '', 3, $this->generateUrl('homepage'));
        }

        $user = $this->getUserService()->getUserAndProfile($record['userId']);
        $certificate = $this->getCertificateService()->get($record['certificateId']);

        return $this->render('certificate/certificate-record.html.twig', [
            'record' => $record,
            'user' => $user,
            'url' => $this->generateUrl('certificate_record', ['recordId' => $record['id']], UrlGeneratorInterface::ABSOLUTE_URL),
            'certificate' => $certificate,
        ]);
    }

    public function certificateImageDownloadAction(Request $request, $recordId)
    {
        $record = $this->getRecordService()->get($recordId);
        $img = $this->getCertificateStrategy($record['targetType'])->getCertificateImg($record);
        $certificate = $this->getCertificateService()->get($record['certificateId']);

        return new Response(base64_decode($img), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="'.$certificate['name'].'.png"',
        ]);
    }

    protected function getCertificateStrategy($type)
    {
        return $this->getBiz()->offsetGet('certificate.strategy_context')->createStrategy($type);
    }

    public function certificateDetailAction(Request $request, $id)
    {
        $certificate = $this->getCertificateService()->get($id);
        if (empty($certificate)) {
            $this->createNewException(CertificateException::NOTFOUND_CERTIFICATE());
        }

        $target = $this->getTarget($certificate['targetId'], $certificate['targetType']);

        $isObtained = $this->getRecordService()->isObtained($this->getCurrentUser()->getId(), $certificate['id']);

        return $this->render('classroom/certificates/detail.html.twig', [
            'certificate' => $certificate,
            'targetType' => $certificate['targetType'],
            'target' => $target,
            'isObtained' => $isObtained,
        ]);
    }

    protected function getTarget($targetId, $targetType)
    {
        if ('classroom' == $targetType) {
            $target = $this->getClassroomService()->getClassroom($targetId);
            if (empty($target)) {
                $this->createNewException(ClassroomException::NOTFOUND_CLASSROOM());
            }
        } else {
            $target = $this->getCourseService()->getCourse($targetId);
            if (empty($target)) {
                $this->createNewException(CourseException::NOTFOUND_COURSE());
            }
        }

        return $target;
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->getBiz()->service('Course:CourseService');
    }

    /**
     * @return ClassroomService
     */
    private function getClassroomService()
    {
        return $this->createService('Classroom:ClassroomService');
    }

    /**
     * @return RecordService
     */
    protected function getRecordService()
    {
        return $this->createService('Certificate:RecordService');
    }

    /**
     * @return CertificateService
     */
    protected function getCertificateService()
    {
        return $this->createService('Certificate:CertificateService');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }
}
