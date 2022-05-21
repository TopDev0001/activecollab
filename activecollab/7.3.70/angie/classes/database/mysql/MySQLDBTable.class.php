<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * MySQL database table implementation.
 *
 * @package angie.library.database
 */
final class MySQLDBTable extends DBTable
{
    const ENGINE_INNODB = 'InnoDB';
    const ENGINE_MEMORY = 'Memory';

    /**
     * Prefered storage engine for this table.
     *
     * @var string
     */
    protected $storage_engine = self::ENGINE_INNODB;

    /**
     * Default table character set.
     *
     * @var string
     */
    protected $character_set = 'utf8mb4'; // old utf8

    /**
     * Default table collation.
     *
     * @var string
     */
    protected $collation = 'utf8mb4_unicode_ci'; // old utf8_general_ci

    /**
     * Create and return new table instance.
     *
     * @param  string       $name
     * @param  bool         $load
     * @return MySQLDBTable
     */
    public static function create($name, $load = false)
    {
        return new self($name, $load);
    }

    // ---------------------------------------------------
    //  Options
    // ---------------------------------------------------

    /**
     * Return array of table options.
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            'ENGINE' => $this->getStorageEngine(),
            'DEFAULT CHARSET' => $this->getCharacterSet(),
            'COLLATE' => $this->getCollation(),
        ];
    }

    // ---------------------------------------------------
    //  Getters and setters
    // ---------------------------------------------------

    /**
     * Return storage_engine.
     *
     * @return string
     */
    public function getStorageEngine()
    {
        return $this->storage_engine;
    }

    /**
     * Set storage_engine.
     *
     * @param  string       $value
     * @return MySQLDBTable
     */
    public function &setStorageEngine($value)
    {
        $this->storage_engine = $value;

        return $this;
    }

    /**
     * Return character_set.
     *
     * @return string
     */
    public function getCharacterSet()
    {
        return $this->character_set;
    }

    /**
     * Set character_set.
     *
     * @param  string       $value
     * @param  string       $collation
     * @return MySQLDBTable
     */
    public function &setCharacterSet($value, $collation = null)
    {
        if ($this->character_set != $value) {
            $this->character_set = $value;
            $this->collation = $collation ? $collation : $this->getDefaultCollationForCharset($value);
        }

        return $this;
    }

    /**
     * Return collation.
     *
     * @return string
     */
    public function getCollation()
    {
        return $this->collation;
    }

    /**
     * Set collation.
     *
     * @param  string       $value
     * @return MySQLDBTable
     */
    public function &setCollation($value)
    {
        $this->collation = $value;

        return $this;
    }

    // ---------------------------------------------------
    //  Utils
    // ---------------------------------------------------

    /**
     * Returns default collation for given charset.
     *
     * @param  string            $charset
     * @return string
     * @throws InvalidParamError
     */
    public function getDefaultCollationForCharset($charset)
    {
        $row = DB::executeFirstRow('SHOW CHARACTER SET LIKE ?', [$charset]);

        if ($row && isset($row['Default collation'])) {
            return $row['Default collation'];
        } else {
            throw new InvalidParamError('charset', $charset, "Unknown MySQL charset '$charset'");
        }
    }
}
