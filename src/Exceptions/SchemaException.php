<?php namespace Wsklad\Exceptions;

defined('ABSPATH') || exit;

use RuntimeException as SystemRuntimeException;

/**
 * SchemaException
 *
 * The database is not in the state the code needs: tables are missing, or the stored
 * schema version is behind the code's.
 *
 * ⚠ This class exists because `Data\Storage` throws it on the "tables are missing" path,
 * and the throw itself was the defect. `Data\Storage` referenced a class that arrived
 * with a later release, so the one moment the site most needed a sentence it could
 * explain — "your tables are gone, reactivate the plugin" — instead produced
 *
 *     Error: Class "Wsklad\Exceptions\SchemaException" not found
 *
 * A missing class on the error path is worse than no error path, because it takes the
 * diagnosis with it. Anything that can be thrown on a recovery path has to be present in
 * the release that throws it.
 *
 * @package Wsklad\Exceptions
 * @since 0.10.0
 */
class SchemaException extends SystemRuntimeException
{}
