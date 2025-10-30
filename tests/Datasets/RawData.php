<?php

namespace Tests\Datasets;

dataset(
    'simple method and path combinations',
    [
        'root path'             => ['GET', '/', 'GET /'],
        'get request and fixed path'             => ['GET', '/test/route', 'GET /test/route'],
        'get request and path with parameter'    => ['GET', '/test/route/{myParam}', 'GET /test/route/{myParam}'],
        'put request and fixed path'             => ['PUT', '/test/route', 'PUT /test/route'],
        'put request and path with parameter'    => ['PUT', '/test/route/{myParam}', 'PUT /test/route/{myParam}'],
        'post request and fixed path'            => ['POST', '/test/route', 'POST /test/route'],
        'post request and path with parameter'   => ['POST', '/test/route/{myParam}', 'POST /test/route/{myParam}'],
        'delete request and fixed path'          => ['DELETE', '/test/route', 'DELETE /test/route'],
        'delete request and path with parameter' => ['DELETE', '/test/route/{myParam}', 'DELETE /test/route/{myParam}'],
        'option request and fixed path'          => ['OPTION', '/test/route', 'OPTION /test/route'],
        'option request and path with parameter' => ['OPTION', '/test/route/{myParam}', 'OPTION /test/route/{myParam}'],
        'head request and fixed path'            => ['HEAD', '/test/route', 'HEAD /test/route'],
        'head request and path with parameter'   => ['HEAD', '/test/route/{myParam}', 'HEAD /test/route/{myParam}'],
    ]
);

dataset(
    'possible redis commands',
    [
        'empty query' => ['', [], 'Unknown'],
        'only eval'   => ['eval', [], 'eval'],
        'eval SET'    => ['eval', ['SET'], 'SET'],
        'eval empty'  => ['eval', [''], 'Unknown'],
        'SET'         => ['SET', ['1234565'], 'SET'],
    ]
);

dataset(
    'possible sql queries',
    [
        'empty query'            => ['', '', []],
        'simple select query'    => ['SELECT * FROM exampleTable', 'SELECT', ['exampleTable']],
        'select query with join' => [
            'SELECT * FROM exampleTable1 JOIN exampleTable2 ON exampleTable2.id = exampleTable1.id',
            'SELECT',
            ['exampleTable1'],
        ],
        'insert query'      => ['INSERT INTO exampleTable VALUES (?,?)', 'INSERT', ['exampleTable']],
        'update query'      => ['UPDATE exampleTable SET name = ?', 'UPDATE', ['exampleTable']],
        'delete query'      => ['DELETE FROM exampleTable', 'DELETE', ['exampleTable']],
        'create table'      => ['CREATE TABLE exampleTable (id int)', 'CREATE', []],
        'drop  table'       => ['DROP TABLE exampleTable', 'DROP', []],
        'alter table'       => ['ALTER TABLE exampleTable ADD title varchar', 'ALTER', []],
        'execute procedure' => ['EXEC myExampleProcedure', 'EXEC', []],
    ]
);

dataset(
    /*
     * @see https://github.com/elastic/apm/blob/main/tests/agents/json-specs/sql_signature_examples.json
     */
    'elastic apm sql mapping',
    [
        ['', ''],
        [' ', ''],
        ['SELECT * FROM foo.bar', 'SELECT FROM foo.bar'],
        ['SELECT * FROM foo.bar.baz', 'SELECT FROM foo.bar.baz'],
        ['SELECT * FROM `foo.bar`', 'SELECT FROM foo.bar'],
        ['SELECT * FROM "foo.bar"', 'SELECT FROM foo.bar'],
        ['SELECT * FROM [foo.bar]', 'SELECT FROM foo.bar'],
        ['SELECT (x, y) FROM foo,bar,baz', 'SELECT FROM foo'],
        ['SELECT * FROM foo JOIN bar', 'SELECT FROM foo'],
        ["SELECT * FROM dollar{$bill}", "SELECT FROM dollar{$bill}"],
        //        [ "SELECT id FROM \"myta\n-æøåble\" WHERE id = 2323",  "SELECT FROM myta\n-æøåble"],
        ["SELECT * FROM foo-- abc\n./*def*/bar", 'SELECT FROM foo.bar'],
        ["SELECT *,(SELECT COUNT(*) FROM table2 WHERE table2.field1 = table1.id) AS count FROM table1 WHERE table1.field1 = 'value'", 'SELECT FROM table1'],
        ['SELECT * FROM (SELECT foo FROM bar) AS foo_bar', 'SELECT'],
        ['DELETE FROM foo.bar WHERE baz=1', 'DELETE FROM foo.bar'],
        ['UPDATE IGNORE foo.bar SET bar=1 WHERE baz=2', 'UPDATE foo.bar'],
        ['UPDATE ONLY foo AS bar SET baz=1', 'UPDATE foo'],
        ['INSERT INTO foo.bar (col) VALUES(?)', 'INSERT INTO foo.bar'],
        ['INSERT LOW_PRIORITY IGNORE INTO foo.bar (col) VALUES(?)', 'INSERT INTO foo.bar'],
        ['CALL foo(bar, 123)', 'CALL foo'],
        ['ALTER TABLE foo ADD ()', 'ALTER'],
        ['CREATE TABLE foo ...', 'CREATE'],
        ['DROP TABLE foo', 'DROP'],
        ['SAVEPOINT x_asd1234', 'SAVEPOINT'],
        ['BEGIN', 'BEGIN'],
        ['COMMIT', 'COMMIT'],
        ['ROLLBACK', 'ROLLBACK'],
        ['SELECT * FROM (SELECT EOF', 'SELECT'],
        //        [ "SELECT 'neverending literal FROM (SELECT * FROM ...",  "SELECT"],
        ['INSERT COIN TO PLAY', 'INSERT'],
        ['INSERT $2 INTO', 'INSERT'],
        ['UPDATE 99', 'UPDATE'],
        ['DELETE 99', 'DELETE'],
        ['DELETE FROM', 'DELETE'],
        ['CALL', 'CALL'],
    ]
);
