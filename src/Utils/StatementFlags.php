<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Utils;

final class StatementFlags
{
    /**
     * select ... DISTINCT ...
     */
    public bool $distinct = false;

    /**
     * drop ... DATABASE ...
     */
    public bool $dropDatabase = false;

    /**
     * ... GROUP BY ...
     */
    public bool $group = false;

    /**
     * ... HAVING ...
     */
    public bool $having = false;

    /**
     * INSERT ... or REPLACE ... or DELETE ...
     */
    public bool $isAffected = false;

    /**
     * select ... PROCEDURE ANALYSE( ... ) ...
     */
    public bool $isAnalyse = false;

    /**
     * select COUNT( ... ) ...
     */
    public bool $isCount = false;

    /**
     * DELETE ...
     *
     * @deprecated Use {@see self::$queryType} instead.
     */
    public bool $isDelete = false;

    /**
     * EXPLAIN ...
     *
     * @deprecated Use {@see self::$queryType} instead.
     */
    public bool $isExplain = false;

    /**
     * select ... INTO OUTFILE ...
     */
    public bool $isExport = false;

    /**
     * select FUNC( ... ) ...
     */
    public bool $isFunc = false;

    /**
     * select ... GROUP BY ... or select ... HAVING ...
     */
    public bool $isGroup = false;

    /**
     * INSERT ... or REPLACE ... or LOAD DATA ...
     */
    public bool $isInsert = false;

    /**
     * ANALYZE ... or CHECK ... or CHECKSUM ... or OPTIMIZE ... or REPAIR ...
     */
    public bool $isMaint = false;

    /**
     * CALL ...
     */
    public bool $isProcedure = false;

    /**
     * REPLACE ...
     *
     * @deprecated Use {@see self::$queryType} instead.
     */
    public bool $isReplace = false;

    /**
     * SELECT ...
     *
     * @deprecated Use {@see self::$queryType} instead.
     */
    public bool $isSelect = false;

    /**
     * SHOW ...
     *
     * @deprecated Use {@see self::$queryType} instead.
     */
    public bool $isShow = false;

    /**
     * Contains a subquery.
     */
    public bool $isSubQuery = false;

    /**
     * ... JOIN ...
     */
    public bool $join = false;

    /**
     * ... LIMIT ...
     */
    public bool $limit = false;

    /**
     * TODO
     */
    public bool $offset = false;

    /**
     * ... ORDER ...
     */
    public bool $order = false;

    /**
     * The type of the statement (which is usually the first keyword).
     */
    public StatementType|null $queryType = null;

    /**
     * Whether a page reload is required.
     */
    public bool $reload = false;

    /**
     * SELECT ... FROM ...
     */
    public bool $selectFrom = false;

    /**
     * ... UNION ...
     */
    public bool $union = false;
}
