<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class DuplicateYouthRecordException extends RuntimeException
{
    public const CODE = 'DUPLICATE_YOUTH_RECORD';

    public const TITLE = 'Existing Youth Record Found';

    public const ADMIN_MESSAGE = 'We found an existing youth record that matches some of the personal information you provided. To maintain accurate youth records and prevent duplicate accounts, your registration cannot be completed.';

    public const DEFAULT_MESSAGE = "We found an existing youth record that matches some of the personal information you provided. To maintain accurate youth records and prevent duplicate accounts, your registration cannot be completed.\n\nIf you already have an account, please use the Forgot Password option to recover access to your existing account.";

    public function __construct(string $message = self::DEFAULT_MESSAGE)
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'code' => self::CODE,
            'title' => self::TITLE,
            'message' => $this->getMessage(),
        ], Response::HTTP_CONFLICT);
    }
}
