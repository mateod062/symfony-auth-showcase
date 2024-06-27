<?php

namespace App\Controller;

use App\Message\EmailNotification;
use App\Service\UserExport\Interface\UserExportServiceInterface;
use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class ExportController extends AbstractController
{
    public function __construct(
        private readonly UserExportServiceInterface $userExportService,
        private readonly MessageBusInterface $messageBus,
        private readonly CacheItemPoolInterface $cache
    )
    {
    }

    /**
     * @throws ExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     */
    #[Route('/api/export/{format}', name: 'export_users', methods: ['GET'])]
    public function exportUsers(Request $request, string $format): JsonResponse
    {
        $queryParams = $request->query->all();

        $filter = [];
        if (isset($queryParams['name'])) {
            $filter['name'] = $queryParams['name'];
        }
        if (isset($queryParams['contract_start_date'])) {
            $filter['contractStartDate'] = new DateTime($queryParams['contract_start_date']);
        }
        if (isset($queryParams['contract_end_date'])) {
            $filter['contractEndDate'] = new DateTime($queryParams['contract_end_date']);
        }
        if (isset($queryParams['type'])) {
            $filter['type'] = $queryParams['type'];
        }
        if (isset($queryParams['verified'])) {
            $filter['verified'] = filter_var($queryParams['verified'], FILTER_VALIDATE_BOOLEAN);
        }

        $email = $request->request->get('email');

        $cacheKey = 'export_request_' . md5($email);
        $cacheItem = $this->cache->getItem($cacheKey);

        if (!$cacheItem->isHit()) {
            $cacheItem->set(1);
            $cacheItem->expiresAfter(60);
        } else {
            $requestCount = $cacheItem->get();
            if ($requestCount >= 2) {
                return $this->json(['error' => 'Too many requests. Please try again later.'], 429);
            }
            $cacheItem->set($requestCount + 1);
        }
        $this->cache->save($cacheItem);

        if (!in_array($format, ['csv', 'pdf'])) {
            return $this->json(['error' => 'Invalid format'], 400);
        }

        if ($format === 'csv') {
            $fileName = $this->userExportService->exportToCsv($filter);
        } else {
            $fileName = $this->userExportService->exportToPdf($filter);
        }

        $this->messageBus->dispatch(new EmailNotification($fileName, $email, $format));

        return $this->json(['message' => 'Export started'], 202);
    }
}
