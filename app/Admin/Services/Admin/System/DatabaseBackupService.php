<?php

namespace VistiqAdmin\Services\Admin\System;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatabaseBackupService
{
    public function export(bool $includeSchema, bool $includeData): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('Sao lưu hiện hỗ trợ MySQL và SQLite.');
        }

        $tables = $this->tableNames($connection, $driver);

        if ($tables === []) {
            throw new RuntimeException('Không tìm thấy bảng nào trong cơ sở dữ liệu hiện tại. Không tạo file backup rỗng.');
        }

        echo "-- Vistiq Database Backup v2\n";
        echo "-- Driver: {$driver}\n";

        if ($driver === 'sqlite') {
            echo 'PRAGMA foreign_keys = OFF;'.PHP_EOL;
        } else {
            echo 'SET FOREIGN_KEY_CHECKS=0;'.PHP_EOL;
        }

        if ($includeSchema) {
            foreach ($tables as $table) {
                $createStatement = $this->createStatement($connection, $table, $driver);

                if ($createStatement !== null) {
                    echo $this->makeCreateTableNonDestructive($createStatement).';'.PHP_EOL;
                }
            }
            if ($driver === 'sqlite') {
                foreach ($connection->select("SELECT sql FROM sqlite_master WHERE type = 'index' AND sql IS NOT NULL ORDER BY name") as $index) {
                    echo $this->makeCreateIndexNonDestructive($index->sql).';'.PHP_EOL;
                }
            }
        }

        if ($includeData) {
            foreach ($tables as $table) {
                $columns = $connection->getSchemaBuilder()->getColumnListing($table);

                if ($columns === []) {
                    continue;
                }

                echo 'DELETE FROM '.$this->quoteIdentifier($table, $driver).';'.PHP_EOL;
                $quotedColumns = implode(', ', array_map(fn (string $column): string => $this->quoteIdentifier($column, $driver), $columns));
                $query = $connection->table($table)->select($columns);

                foreach ($query->cursor() as $row) {
                    $values = array_map(fn (string $column): string => $this->quoteValue($row->{$column}, $connection, $driver), $columns);

                    echo 'INSERT INTO '.$this->quoteIdentifier($table, $driver).' ('.$quotedColumns.') VALUES ('.implode(', ', $values).');'.PHP_EOL;
                }
            }
        }

        if ($driver === 'sqlite') {
            echo 'PRAGMA foreign_keys = ON;'.PHP_EOL;
        } else {
            echo 'SET FOREIGN_KEY_CHECKS=1;'.PHP_EOL;
        }
    }

    public function restore(string $sql): int
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('Phục hồi hiện hỗ trợ MySQL và SQLite.');
        }

        if (! preg_match('/\A-- Vistiq Database Backup v(?:1|2)\R-- Driver: (mysql|sqlite)\R/', $sql, $matches)) {
            throw new RuntimeException('File không phải bản sao lưu SQL do Vistiq xuất.');
        }

        if ($matches[1] !== $driver) {
            throw new RuntimeException('Loại cơ sở dữ liệu trong file không khớp với ứng dụng.');
        }

        $statements = iterator_to_array($this->statements($sql));
        foreach ($statements as $statement) {
            if (! $this->isAllowedStatement($statement, $driver)) {
                throw new RuntimeException('File chứa câu lệnh SQL không được hỗ trợ.');
            }
        }
        $statementCount = 0;

        try {
            foreach ($statements as $statement) {
                $connection->unprepared($statement);
                $statementCount++;
            }
        } finally {
            if ($driver === 'sqlite') {
                $connection->unprepared('PRAGMA foreign_keys = ON');
            } else {
                $connection->unprepared('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        if ($statementCount === 0) {
            throw new RuntimeException('File sao lưu không có câu lệnh SQL để phục hồi.');
        }

        return $statementCount;
    }

    private function createStatement(Connection $connection, string $table, string $driver): ?string
    {
        if ($driver === 'mysql') {
            $result = $connection->selectOne('SHOW CREATE TABLE '.$this->quoteIdentifier($table, $driver));

            return $result === null ? null : array_values((array) $result)[1];
        }

        return $connection->selectOne(
            'SELECT sql FROM sqlite_master WHERE type = ? AND name = ?',
            ['table', $table],
        )?->sql;
    }

    /** @return list<string> */
    private function tableNames(Connection $connection, string $driver): array
    {
        $schema = $driver === 'mysql' ? $connection->getDatabaseName() : null;

        return array_values(array_unique(
            $connection->getSchemaBuilder()->getTableListing(schema: $schema, schemaQualified: false),
        ));
    }

    private function quoteIdentifier(string $identifier, string $driver): string
    {
        $quote = $driver === 'mysql' ? '`' : '"';

        return $quote.str_replace($quote, $quote.$quote, $identifier).$quote;
    }

    private function makeCreateTableNonDestructive(string $statement): string
    {
        return preg_replace('/\ACREATE\s+TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', trim($statement), 1) ?? $statement;
    }

    private function makeCreateIndexNonDestructive(string $statement): string
    {
        return preg_replace('/\ACREATE\s+((?:UNIQUE\s+)?INDEX\s+)/i', 'CREATE $1IF NOT EXISTS ', trim($statement), 1) ?? $statement;
    }

    private function quoteValue(mixed $value, Connection $connection, string $driver): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $quoted = $connection->getPdo()->quote((string) $value);

        if ($quoted === false) {
            $hex = bin2hex((string) $value);

            return $driver === 'mysql' ? "X'{$hex}'" : "X'{$hex}'";
        }

        return $quoted;
    }

    /** @return iterable<string> */
    private function statements(string $sql): iterable
    {
        $statement = '';
        $quote = null;
        $length = strlen($sql);

        for ($index = 0; $index < $length; $index++) {
            $character = $sql[$index];
            $nextCharacter = $sql[$index + 1] ?? '';

            if ($quote === null && $character === '-' && $nextCharacter === '-') {
                while ($index < $length && $sql[$index] !== "\n") {
                    $index++;
                }

                continue;
            }

            if ($quote === null && $character === '#') {
                while ($index < $length && $sql[$index] !== "\n") {
                    $index++;
                }

                continue;
            }

            if ($quote === null && $character === '/' && $nextCharacter === '*') {
                $index += 2;

                while ($index < $length && ! ($sql[$index] === '*' && ($sql[$index + 1] ?? '') === '/')) {
                    $index++;
                }

                $index++;

                continue;
            }

            if ($quote !== null) {
                $statement .= $character;

                if ($character === '\\' && $quote !== '`') {
                    $statement .= $sql[++$index] ?? '';

                    continue;
                }

                if ($character === $quote) {
                    if ($nextCharacter === $quote) {
                        $statement .= $sql[++$index];
                    } else {
                        $quote = null;
                    }
                }

                continue;
            }

            if (in_array($character, ["'", '"', '`'], true)) {
                $quote = $character;
                $statement .= $character;

                continue;
            }

            if ($character === ';') {
                if (trim($statement) !== '') {
                    yield trim($statement);
                }

                $statement = '';

                continue;
            }

            $statement .= $character;
        }

        if ($quote !== null) {
            throw new RuntimeException('File SQL có chuỗi chưa đóng.');
        }
        if (trim($statement) !== '') {
            throw new RuntimeException('File SQL thiếu dấu kết thúc câu lệnh.');
        }
    }

    private function isAllowedStatement(string $statement, string $driver): bool
    {
        if (preg_match($driver === 'mysql' ? '/\\ASET\\s+FOREIGN_KEY_CHECKS\\s*=\\s*[01]\\z/i' : '/\\APRAGMA\\s+foreign_keys\\s*=\\s*(?:ON|OFF)\\z/i', $statement)) {
            return true;
        }
        if ($driver === 'sqlite' && preg_match('/\\ACREATE\\s+(?:UNIQUE\\s+)?INDEX\\s+(?:IF\\s+NOT\\s+EXISTS\\s+)?["`]?([a-zA-Z0-9_]+)["`]?\\s+ON\\s+["`]?([a-zA-Z0-9_]+)["`]?\\s*\\((["`a-zA-Z0-9_,\\s]+)\\)\\z/i', $statement, $index)) {
            return in_array($index[2], $this->tableNames(DB::connection(), $driver), true);
        }
        if (! preg_match('/\\A(DROP\\s+TABLE\\s+IF\\s+EXISTS|CREATE\\s+TABLE(?:\\s+IF\\s+NOT\\s+EXISTS)?|DELETE\\s+FROM|INSERT\\s+INTO)\\s+[`"]?([a-zA-Z0-9_]+)[`"]?(?=\\s|\\(|$)(.*)\\z/is', $statement, $matches)) {
            return false;
        }
        if (! in_array($matches[2], $this->tableNames(DB::connection(), $driver), true)) {
            return false;
        }
        $tail = $matches[3];
        if (preg_match('/\\ADROP|\\ADELETE/i', $matches[1])) {
            return trim($tail) === '';
        }
        $unquoted = preg_replace('/\x27(?:\x27\x27|[^\x27])*\x27|"(?:""|[^"])*"|`[^`]*`/s', '', $tail);
        if (preg_match('/\\b(?:SELECT|UNION|OUTFILE|INFILE|LOAD_FILE|SLEEP|BENCHMARK|ATTACH|DETACH|TRIGGER|PROCEDURE|FEDERATED|DIRECTORY)\\b/i', $unquoted)) {
            return false;
        }
        if (str_starts_with(strtoupper($matches[1]), 'INSERT') && preg_match('/[A-Za-z_][A-Za-z_0-9]*\\s*\\(/', preg_replace('/\\AVALUES\\b/i', '', substr($unquoted, strpos(strtoupper($unquoted), 'VALUES'))))) {
            return false;
        }
        $allowed = $driver === 'mysql'
            ? '/\A(?:SET\s+FOREIGN_KEY_CHECKS\s*=\s*[01]|DROP\s+TABLE\s+IF\s+EXISTS\s+|CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s+|DELETE\s+FROM\s+|INSERT\s+INTO\s+)/i'
            : '/\A(?:PRAGMA\s+foreign_keys\s*=\s*(?:ON|OFF)|DROP\s+TABLE\s+IF\s+EXISTS\s+|CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s+|DELETE\s+FROM\s+|INSERT\s+INTO\s+)/i';

        return preg_match($allowed, ltrim($statement)) === 1;
    }
}
