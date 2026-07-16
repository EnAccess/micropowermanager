<?php

namespace App\Utils\ScrambleExtensions;

use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\Header;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\Literal\LiteralIntegerType;
use Dedoc\Scramble\Support\Type\Literal\LiteralStringType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\BinaryFileResponseToSchema;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\StreamedResponseToSchema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Scramble's built-in StreamedResponseToSchema documents every non-JSON, non-SSE
 * streamed response body as a plain string, which is wrong for file downloads of
 * binary formats such as xlsx. This extension documents streamed responses whose
 * literal `Content-Type` header is a binary mime type with a `format: binary`
 * string schema instead. Everything else (SSE, `text/csv`, JSON streams, or no
 * literal content type) falls through to the built-in extension by returning null.
 * https://github.com/dedoc/scramble/issues/1224
 */
class BinaryStreamedResponseToSchema extends TypeToSchemaExtension {
    public function shouldHandle(Type $type): bool {
        return $type instanceof Generic
            && $type->isInstanceOf(StreamedResponse::class)
            && count($type->templateTypes) === 3;
    }

    /**
     * @param Generic $type
     */
    public function toResponse(Type $type): ?Response {
        $contentType = $this->contentTypeFromHeaders($type->templateTypes[2 /* THeaders */]);

        if ($contentType === null || in_array($contentType, BinaryFileResponseToSchema::$nonBinaryMimeTypes)) {
            return null;
        }

        $statusType = $type->templateTypes[1 /* TStatus */];

        if (!$statusType instanceof LiteralIntegerType) {
            return null;
        }

        return Response::make($statusType->value)
            ->setDescription('A streamed file download.')
            ->setContent($contentType, Schema::fromType(new StringType()->format('binary')))
            ->setHeaders([
                'Transfer-Encoding' => new Header(
                    required: true,
                    schema: Schema::fromType(new StringType()->enum(['chunked'])),
                ),
            ]);
    }

    /**
     * Mirrors the lookup StreamedResponseToSchema does internally (it is private
     * there): a `Content-Type` entry with a literal string value in the headers
     * array passed at the call site.
     *
     * @see StreamedResponseToSchema
     */
    private function contentTypeFromHeaders(Type $headersType): ?string {
        if (!$headersType instanceof KeyedArrayType) {
            return null;
        }

        foreach ($headersType->items as $item) {
            if (is_string($item->key)
                && Str::lower($item->key) === 'content-type'
                && $item->value instanceof LiteralStringType) {
                return $item->value->value;
            }
        }

        return null;
    }
}
