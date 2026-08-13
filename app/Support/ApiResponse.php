<?php

namespace App\Support;

use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;

class ApiResponse implements Responsable
{
    const DEFAULT_STATUS = 200;

    protected int $status;

    protected ?string $message;

    protected mixed $data;

    protected mixed $input;

    protected ?MessageBag $errors;

    protected ?array $paginator;

    public function __construct()
    {
        $this->reset();
    }

    public function reset(): self
    {
        $this->status = static::DEFAULT_STATUS;
        $this->message = null;
        $this->data = null;
        $this->input = null;
        $this->errors = null;
        $this->paginator = null;

        return $this;
    }

    public function status(int $status, bool $setMessage = true): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function message(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function data(mixed $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getData($key = null): mixed
    {
        return Arr::get($this->data, $key);
    }

    public function input(mixed $input): self
    {
        $this->input = $input;

        return $this;
    }

    public function getInput(): mixed
    {
        return $this->input;
    }

    public function errors(?MessageBag $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    public function getErrors(): ?MessageBag
    {
        return $this->errors;
    }

    public function paginator(null|Paginator|LengthAwarePaginator $paginator): self
    {
        $this->paginator = ($paginator ? [
            'class' => $paginator::class,
            'parameters' => [
                'items' => [],
                'perPage' => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'options' => $paginator->getOptions(),
                'onLastPage' => $paginator->onLastPage(),
                'total' => ($paginator instanceof LengthAwarePaginator ? $paginator->total() : null),
            ],
        ] : null);

        return $this;
    }

    public function getPaginator($path = '', $itemsDataKey = null, $pageName = 'page'): null|Paginator|LengthAwarePaginator
    {
        if (! $this->paginator) {
            return null;
        }

        if ($itemsDataKey) {
            $this->paginator['parameters']['items'] = (Arr::get($this->data, $itemsDataKey) ?: []);
        }

        return app($this->paginator['class'], $this->paginator['parameters'])
            ->setPath($path)
            ->setPageName($pageName);
    }

    public function isSuccessful(): bool
    {
        return $this->getStatus() >= 200 and $this->getStatus() < 300;
    }

    public function abortUnSuccessful()
    {
        abort_if(! $this->isSuccessful(), $this->getStatus(), $this->getMessage());

        return $this;
    }

    public function toResponse($request)
    {
        return response([
            'status' => $this->getStatus(),
            'message' => $this->getMessage(),
            'data' => $this->getData(),
            'errors' => ($this->getErrors() instanceof MessageBag ? $this->getErrors()->toArray() : []),
            'paginator' => ($this->paginator ? [
                'perPage' => $this->paginator['parameters']['perPage'],
                'currentPage' => $this->paginator['parameters']['currentPage'],
                'total' => $this->paginator['parameters']['total'],
                'onLastPage' => $this->paginator['parameters']['onLastPage'],
            ] : null),
        ], $this->getStatus());
    }

    public static function make(int $status = self::DEFAULT_STATUS): static
    {
        return app(static::class)->status($status);
    }

    public static function makeFromValidator(Validator $validator): static
    {
        $messages = $validator->errors()->all();
        if (count($messages) && is_string($messages[0])) {
            $message = array_shift($messages);
            if ($count = count($messages)) {
                $pluralized = $count === 1 ? 'error' : 'errors';
                $message .= ' '.$validator->getTranslator()->choice("(and :count more $pluralized)", $count, compact('count'));
            }
        } else {
            $message = $validator->getTranslator()->get('The given data was invalid.');
        }

        return ApiResponse::make(422)->message($message)->errors($validator->errors());
    }
}
