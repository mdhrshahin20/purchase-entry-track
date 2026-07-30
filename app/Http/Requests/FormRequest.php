<?php

namespace App\Http\Requests;

use App\Foundation\Request;
use App\Foundation\Response;
use App\Foundation\Validator;

/**
 * Laravel-style Form Request — validates then exposes cleaned input.
 */
abstract class FormRequest extends Request
{
    /** @var array<string, mixed> */
    private array $validated = [];

    public static function createFrom(Request $request): static
    {
        $ref = new \ReflectionClass($request);
        $query = $ref->getProperty('query');
        $requestBag = $ref->getProperty('request');
        $server = $ref->getProperty('server');
        $cookies = $ref->getProperty('cookies');

        $query->setAccessible(true);
        $requestBag->setAccessible(true);
        $server->setAccessible(true);
        $cookies->setAccessible(true);

        /** @var static $instance */
        $instance = new static(
            $query->getValue($request),
            $requestBag->getValue($request),
            $server->getValue($request),
            $cookies->getValue($request)
        );

        return $instance;
    }

    /** @return array<string, list<string>> */
    abstract public function rules(): array;

    /** @return array<string, mixed> */
    protected function prepareForValidation(): array
    {
        return $this->all();
    }

    public function validateResolved(): void
    {
        $data = $this->prepareForValidation();
        $validator = new Validator($data);

        if (!$validator->validate($this->rules())) {
            Response::json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422)->send();
            exit;
        }

        $this->validated = $data;
    }

    /** @return array<string, mixed> */
    public function validated(): array
    {
        return $this->validated;
    }
}
