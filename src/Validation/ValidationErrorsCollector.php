<?php

declare(strict_types=1);

namespace App\Validation;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationErrorsCollector
{
    public function __construct(
        private readonly ConstraintViolationListInterface $violationList
    ) {
    }

    /**
     * Собираем массив ошибок в удобном формате для работы на фронте
     */
    public function getErrors(): array
    {
        $errors = [];
        foreach ($this->violationList as $item) {
            $code = $this->prepareCode($item->getPropertyPath());
            $errors[$code] = [
                'fieldCode' => $code,
                'message'   => $item->getMessage(),
            ];
        }
        return $errors;
    }

    /**
     * Упрощаем названия полей т.к. никаких вложенностей штатными средствами не планируется
     */
    private function prepareCode(string $getPropertyPath): string
    {
        return str_replace(['[', ']'], [], $getPropertyPath);
    }
}
