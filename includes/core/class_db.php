<?php

class DB
{

    private static $db;

    public static function connect(): PDO
    {
        if (!DB::$db) {
            try {
                DB::$db = new PDO(
                    'mysql:dbname=' . DB_NAME . ';host=' . DB_HOST . ';charset=utf8mb4;',
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
                );
            } catch (PDOException $e) {
                print 'Error!: ' . $e->getMessage() . '<br/>';
                die();
            }
        }
        return DB::$db;
    }

    public static function query($q): false|PDOStatement
    {
        return DB::connect()->query($q);
    }

    public static function prepareAndExec(string $query, array $values): false|PDOStatement
    {
        $statement = self::connect()->prepare($query);
        $statement->execute($values);
        return $statement;
    }

    static function getOne(string $query, array $values): array
    {
        return self::prepareAndExec($query, $values)->fetch();
    }

    public static function fetch_row(PDOStatement $q) {
        return $q->fetch();
    }

    public static function error() {
        $res = DB::connect()->errorInfo();
        trigger_error($res[2], E_USER_WARNING);
        return $res[2];
    }

}
