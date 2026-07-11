<?php

namespace App\Utils\ScrambleExtensions;

use Dedoc\Scramble\Infer\Extensions\Event\MethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\MethodReturnTypeExtension;
use Dedoc\Scramble\Support\Type\ArrayType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\Literal\LiteralIntegerType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\StringType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Scramble cannot see through `Storage::download(...)` (the facade resolves to
 * FilesystemManager, whose `download` lives behind `__call`), so file-download
 * endpoints would be documented as a generic JSON object instead of a file.
 * This extension types those calls as the streamed response Scramble knows how
 * to document; the response content type is picked up from a literal
 * `Content-Type` header passed to `Storage::download()` at the call site.
 */
class StorageDownloadTypeInfer implements MethodReturnTypeExtension {
    public function shouldHandle(ObjectType $type): bool {
        return $type->isInstanceOf(FilesystemManager::class)
            || $type->isInstanceOf(Filesystem::class);
    }

    public function getMethodReturnType(MethodCallEvent $event): ?Type {
        if ($event->name !== 'download') {
            return null;
        }

        return new Generic(StreamedResponse::class, [
            new StringType(),
            new LiteralIntegerType(200),
            $event->getArg('headers', 2, new ArrayType()),
        ]);
    }
}
