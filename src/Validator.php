<?php

namespace BigFish\Hub3\Api;

use JsonSchema\Uri\UriRetriever;
use JsonSchema\RefResolver;
use JsonSchema\Validator AS SchemaValidator;

class Validator
{
    /** Relative path to the schema directory. */
    const SCHEMA_DIR = '/../web/schema';

    /** The schema to use for validating requests. */
    const REQUEST_SCHEMA = 'request.json';

    /**
     * Validates request contents for the `POST /barcode` endpoint.
     */
    public function validate(\stdClass $data)
    {
        $schemaDir = realpath(__DIR__ . self::SCHEMA_DIR) . DIRECTORY_SEPARATOR;

        // Hack to form proper URIs on Windows
        if (DIRECTORY_SEPARATOR === '\\') {
            $schemaDir = strtr($schemaDir, '\\', '/');
        }

        $schemaPath = $schemaDir . self::REQUEST_SCHEMA;

        $retriever = new UriRetriever();
        $schema = $retriever->retrieve("file://" . $schemaPath);

        $refResolver = new RefResolver($retriever);
        $refResolver->resolve($schema, 'file://' . $schemaDir);

        $validator = new SchemaValidator();
        $validator->check($data, $schema);

        $errors = [];
        if (!$validator->isValid()) {
            foreach ($validator->getErrors() as $error) {
                $property = $error['property'];
                $message = $error['message'];

                if (!empty($property)) {
                    $message = "$property: $message";
                }

                $errors[] = $message;
            }
        }

        return $errors;
    }
}
