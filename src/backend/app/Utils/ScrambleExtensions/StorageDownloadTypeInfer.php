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
 * `download()` is only implemented on the concrete FilesystemAdapter, and no
 * static path leads Scramble there: the `Storage` facade resolves to
 * FilesystemManager, which forwards unknown methods to the default disk via
 * `__call` (declared `@return mixed`), and `Storage::disk()` is typed as the
 * Filesystem contract, which does not declare `download()` either. With no
 * reachable method definition to infer a return type from, Scramble documents
 * these endpoints as a generic JSON object instead of a file download.
 *
 * This extension supplies the missing return type — the StreamedResponse that
 * `download()` returns at runtime — carrying the literal headers from the call
 * site so the schema extensions can derive the response content type (and mark
 * binary formats accordingly, see BinaryStreamedResponseToSchema).
 * https://github.com/dedoc/scramble/discussions/1223
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
