<?php

declare(strict_types=1);

namespace App\Tests\Support;

/**
 * Compares a response with a JSON file kept next to the test, so that the whole contract with
 * the SPA is visible and any change of it shows up as a diff. `UPDATE_SNAPSHOTS=1` rewrites the files.
 *
 * Database ids depend on the order tests run in, so they are written as "<id>";
 * check the ids that matter with `idsAt()`.
 */
trait MatchesJsonSnapshots
{
    private static function assertMatchesJsonSnapshot(string $name, mixed $actual): void
    {
        $file = self::snapshotFile($name);
        $json = json_encode(
            self::withoutIds($actual),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        ) . "\n";

        if (getenv('UPDATE_SNAPSHOTS') === '1') {
            if (! is_dir(dirname($file))) {
                mkdir(dirname($file), 0777, true);
            }
            file_put_contents($file, $json);
        }

        self::assertFileExists($file, 'Run the test with UPDATE_SNAPSHOTS=1 to create the snapshot');
        self::assertSame(file_get_contents($file), $json, sprintf('The response differs from the snapshot %s', $file));
    }

    /**
     * Every value of the key in the document, in the order they appear.
     *
     * @return list<mixed>
     */
    private static function idsAt(mixed $document, string $key): array
    {
        if (! is_array($document)) {
            return [];
        }

        $values = [];
        foreach ($document as $itemKey => $value) {
            if ($itemKey === $key && ! is_array($value)) {
                $values[] = $value;
                continue;
            }
            array_push($values, ...self::idsAt($value, $key));
        }

        return $values;
    }

    private static function withoutIds(mixed $document): mixed
    {
        if (! is_array($document)) {
            return $document;
        }

        foreach ($document as $key => $value) {
            $document[$key] = in_array($key, ['id', 'accountId', 'instrumentId'], true) && is_int($value)
                ? '<id>'
                : self::withoutIds($value);
        }

        return $document;
    }

    private static function snapshotFile(string $name): string
    {
        $test = new \ReflectionClass(static::class);

        return sprintf('%s/__snapshots__/%s/%s.json', dirname((string) $test->getFileName()), $test->getShortName(), $name);
    }
}
