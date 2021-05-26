<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Utility;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use function fclose;
use function fopen;
use function stream_copy_to_stream;
use function Symfony\Component\String\u;

class DownloadUtility
{
    private ManagerInterface $manager;

    public function __construct(ManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    private function createDisposition(File $file): string
    {
        if ($file instanceof DownloadableFile) {
            return HeaderUtils::makeDisposition(
                $file->isForceDownload() ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE,
                $file->getName() ?? u($file->getOriginalFilename())->ascii()->toString()
            );
        } else {
            return HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                u($file->getOriginalFilename())->ascii()->toString()
            );
        }
    }

    public function createResponse(File $file): StreamedResponse
    {
        $response = new StreamedResponse();
        $disposition = $this->createDisposition($file);
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', $file->getMimeType());
        $response->setLastModified($file->getCreatedAt());
        $response->setPublic();
        $response->setEtag($file->getHash());

        if ($file instanceof DownloadableFile) {
            $expireAt = $file->getExpireAt();
            $response->setExpires($expireAt);
        } else {
            $expireAt = new \DateTimeImmutable("+30 days");
            $response->setExpires($expireAt);
        }

        $stream = $this->manager->readStream($file);
        $response->setCallback(
            static function () use ($stream): void {
                $out = fopen('php://output', 'wb');

                stream_copy_to_stream($stream, $out);

                fclose($out);
                fclose($stream);
            }
        );

        return $response;
    }
}
