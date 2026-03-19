<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Offer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    private SluggerInterface $slugger;

    private string $uniqueId;

    public function __construct(private string $productsDirectory, private string $offersDirectory, private string $protocolDirectory, private string $docDirectory, SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function getSlugger()
    {
        return $this->slugger;
    }

    public function setUniqueId(string $uniqueId)
    {
        $this->uniqueId = $uniqueId ?? 0;
    }

    public function upload(UploadedFile $file, string $type = null, string $filename = null, Offer $offer = null): bool|string
    {
        $originalFilename = $filename ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        try {
            if ($file instanceof UploadedFile) {
                if ($file->getMimeType() === 'image/png') {
                    $fileNameNew = $safeFilename.'.png';
                } elseif ($file->getMimeType() === 'image/jpeg') {
                    $fileNameNew = $safeFilename.'.jpg';
                } else {
                    $fileNameNew = $safeFilename.'.'.($file->getClientOriginalExtension() ?: $file->getExtension() ?: 'jpg');
                }
            }
        } catch (\Exception $e) {
            throw new BadRequestException($e->getMessage());
        }

        $dir = $this->protocolDirectory;
        switch ($type) {
            case 'request':
                $dir = $this->docDirectory.'/../requests/'.$this->uniqueId;
                break;
            case 'user':
                $dir = $this->docDirectory.'/../user';
                break;
            case 'faq':
                $dir = $this->docDirectory.'/../faq';
                break;
            case 'doc':
            case 'docs':
                $dir = $this->docDirectory.'/'.$offer->getId();
                break;
            case 'offerImage':
                $dir = $this->offersDirectory.'/'.$offer->getId().'/image';
                break;
            case 'offer':
                $dir = $this->offersDirectory.'/'.$offer->getId().'/images';
                break;
            case 'offers':
                $dir = $this->offersDirectory;
                break;
            case 'answer':
                $dir = $this->offersDirectory.'/'.$offer->getId().'/answers';
                break;
            case 'answers':
                $dir = $this->offersDirectory.'/answers';
                break;
            case 'question':
                $dir = $this->docDirectory.'/'.$offer->getId().'';
                break;
            case 'product-documents':
                $dir = $this->productsDirectory.'/docs';
                break;
            case 'product-manufacturer':
                $dir = $this->productsDirectory.'/manufactura';
                break;
            case 'product':
            case 'products':
                $dir = $this->productsDirectory;
                break;
            case 'protocol':
                $dir = $this->docDirectory.'/'.$offer->getId();
                break;
            case 'protocols':
                $dir = $this->protocolDirectory;
                break;
        }

        try {
            $filesystem = new Filesystem();
            $file->move($dir, $fileNameNew);
            $filesystem->chmod($dir.'/'.$fileNameNew, 0777);
        } catch (FileException $e) {
            throw new BadRequestException($e->getMessage());
        }

        return $fileNameNew;
    }
}
