<?php

namespace App\Service\UserExport;

use App\Entity\User;
use App\Service\UserExport\Interface\UserExportServiceInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class UserExportService implements UserExportServiceInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer,
        private readonly FilesystemOperator $filesystem,
    )
    {}

    /**
     * @throws FilesystemException
     */
    public function exportToCsv(array $filter): string
    {
        $users = $this->entityManager->getRepository(User::class)->findBy($filter);
        $data = $this->serializer->normalize($users, null, ['groups' => 'export']);
        $csvContent = $this->serializer->encode($data, 'csv', [CsvEncoder::DELIMITER_KEY => ';']);

        $fileName = 'users_' . time() . '.csv';
        $this->filesystem->write($fileName, $csvContent);

        return $fileName;
    }

    /**
     * @throws FilesystemException|LoaderError|SyntaxError|RuntimeError
     */
    public function exportToPdf($filter): string
    {
        $users = $this->entityManager->getRepository(User::class)->findBy($filter);
        $htmlContent = Environment::class->render('export/users.html.twig', ['users' => $users]);

        $pdfContent = Pdf::class->getOutputFromHtml($htmlContent);

        $fileName = 'users_' . time() . '.pdf';
        $this->filesystem->write($fileName, $pdfContent);

        return $fileName;
    }
}