<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class PieceJointeUploader
{
    public function __construct(
        private string $piecesJointesDirectory,
        private SluggerInterface $slugger,
    ) {
    }

    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $nomFichier = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        $file->move($this->piecesJointesDirectory, $nomFichier);

        return $nomFichier;
    }
}