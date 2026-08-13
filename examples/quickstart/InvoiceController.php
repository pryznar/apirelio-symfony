<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class InvoiceController
{
    #[Route('/api/invoices/{id}', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        return new JsonResponse(['id' => $id, 'status' => 'paid']);
    }
}

