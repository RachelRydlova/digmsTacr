<?php

namespace A2bModels;

use A2bFunc\applCache;


/**
 * Class weatherPmodel
 * @package A2bModels
 */
class weatherPmodel extends baseModel
{

    CONST
        TABLE = 'weather';

    /** @var connectLocal */
    public $local;


    /** @var applCache */
    public $cache;


    /**
     * weatherPmodel constructor.
     * @param connectLocal $local
     * @param applCache $cache
     */
    public function __construct(connectLocal $local, applCache $cache)
    {
        $this->local = $local;
        $this->cache = $cache;
    }


    /**
     * @param $table
     * @return \Nette\Database\Table\Selection
     */
    public function getTableInstance($table)
    {
        return $this->local->database->table($table);
    }


    /**
     * Uni metoda pro result
     * @param $sql
     * @param int $fetchStyle
     * @param bool $multi
     * @return array|bool|mixed
     */
    public function getSqlResult($sql, $multi = false, $fetchStyle = \PDO::FETCH_OBJ)
    {
        $result = $this->local->pdo->query($sql);
        if (!$result) {
            return false;
        }
        return $multi ? $result->fetchAll($fetchStyle) : $result->fetch($fetchStyle);
    }


    /**
     * Vrati posledni aktualni zaznam
     * @return bool|mixed|\Nette\Database\Table\IRow
     */
    public function getActualRecord()
    {
        return $this->getTableInstance(self::TABLE)->order('id DESC')->limit(1)->fetch();
    }


    /**
     * Vrati hodnoty sloupce pro vybrany interval nebo datum
     * @param array $columns
     * @param $year
     * @param null $month
     * @param null $day
     * @param null $specialCond
     * @return array|bool|mixed
     */
    public function getMaxMinAvgByDateParse(array $columns, $year, $month = NULL, $day = NULL, $specialCond = NULL)
    {
        $where = " AND YEAR(TS) = $year";
        if ($month !== NULL) {
            $where .= " AND MONTH(TS) = $month";
        }
        if ($day !== NULL) {
            $where .= " AND DAY(TS) = $day";
        }
        if ($specialCond !== NULL) {
            $where .= ' AND ' .$specialCond;
        }

        $functions = [];
        foreach ($columns as $column => $settings) {
            foreach ($settings as $fce => $alias) {
                $functions[] = $fce.'('.$column.') AS '.$alias;
            }
        }
        $functionsString = implode(', ', $functions);

        $sql = "SELECT $functionsString 
                FROM ".self::TABLE."  
                WHERE relative_Pressure >= 850 AND relative_Pressure <= 1300 
                $where ";
        return $this->getSqlResult($sql);
    }


    /**
     * Vrati hodnoty v presnem intervalu
     * @param array $columns
     * @param \DateTimeImmutable $date_from
     * @param null|\DateTimeImmutable $date_to
     * @param null $specialCond
     * @return bool|mixed
     */
    public function getMaxMinAvgFromDateToActual(array $columns, \DateTimeImmutable $date_from, $date_to = NULL, $specialCond = NULL)
    {
        $where = " AND TS > '".$date_from->format('Y-m-d')."'";
        if ($date_to !== NULL) {
            $where .= " AND TS <= '".$date_to->format('Y-m-d')."'";
        }
        if ($specialCond !== NULL) {
            $where .= ' AND ' .$specialCond;
        }
        $functions = [];
        foreach ($columns as $column => $settings) {
            foreach ($settings as $fce => $alias) {
                $functions[] = $fce.'('.$column.') AS '.$alias;
            }
        }
        $functionsString = implode(', ', $functions);
        $sql = "SELECT $functionsString
                FROM ".self::TABLE." 
                WHERE relative_Pressure >= 850 AND relative_Pressure <= 1300 
                $where ";

        return $this->getSqlResult($sql);
    }


    /**
     * Vrati nejcastejsi hodnotu vyskytu pdo dany sloupec
     * @param $column
     * @param $year
     * @param null $month
     * @param null $day
     * @param string $specialCond
     * @return array|bool|mixed
     */
    public function getMostFrequentedValueInColumnByDateParse($column, $year, $month = NULL, $day = NULL, $specialCond = NULL)
    {
        $where = " AND YEAR(TS) = $year";
        if ($month !== NULL) {
            $where .= " AND MONTH(TS) = $month";
        }
        if ($day !== NULL) {
            $where .= " AND DAY(TS) = $day";
        }
        if ($specialCond !== NULL) {
            $where .= ' AND ' .$specialCond;
        }
        $sql = "SELECT $column, COUNT($column) AS value_occurence  
                FROM ".self::TABLE." 
                WHERE relative_Pressure >= 850 AND relative_Pressure <= 1300 
                $where 
                GROUP BY $column 
                ORDER BY value_occurence DESC 
                LIMIT 1";
        return $this->getSqlResult($sql);
    }


    /**
     * Vrati nejcastejsi hodnoty vyskytu v presnem intervalu
     * @param $column
     * @param \DateTimeImmutable $date_from
     * @param null|\DateTimeImmutable $date_to
     * @param string $specialCond
     * @return bool|mixed
     */
    public function getMostFrequentedValueFromDateToActual($column, \DateTimeImmutable $date_from, $date_to = NULL, $specialCond = NULL)
    {
        $where = " AND TS > '".$date_from->format('Y-m-d')."'";
        if ($date_to !== NULL) {
            $where .= " AND TS <= '".$date_to->format('Y-m-d')."'";
        }
        if ($specialCond !== NULL) {
            $where .= ' AND ' .$specialCond;
        }
        $sql = "SELECT $column, COUNT($column) AS value_occurence  
                FROM ".self::TABLE." 
                WHERE relative_Pressure >= 850 AND relative_Pressure <= 1300 
                $where 
                GROUP BY $column 
                ORDER BY value_occurence DESC 
                LIMIT 1";
        return $this->getSqlResult($sql);
    }


    /**
     * Seskupi po dnech vypoctene hodnoty aggregations
     * @param array $columns
     * @param \DateTimeImmutable $date_from
     * @param null $date_to
     * @return array|bool|mixed
     */
    public function getColumnAgrByDay(array $columns, \DateTimeImmutable $date_from, $date_to = NULL)
    {
        $where = " AND TS > '".$date_from->format('Y-m-d')."'";
        if ($date_to !== NULL) {
            $where .= " AND TS <= '".$date_to->format('Y-m-d')."'";
        }

        $functions = [];
        foreach ($columns as $column => $settings) {
            foreach ($settings as $fce => $alias) {
                $functions[] = $fce.'('.$column.') AS '.$alias;
            }
        }
        $functionsString = implode(', ', $functions);
        $sql = "SELECT $functionsString, DATE_FORMAT(TS, '%Y-%m-%d') AS mydate, DAY(TS) AS myday, 
                DATE_FORMAT(TS, '%d.%m') AS keydate 
                FROM ".self::TABLE." 
                WHERE relative_Pressure >= 850 AND relative_Pressure <= 1300 
                $where 
                GROUP BY mydate
                ORDER BY mydate";
        return $this->getSqlResult($sql, true);
    }


    /**
     * Pocty smeru vetru v zadanem intervalu
     * @param $year
     * @param null $month
     * @param null $day
     * @return array|bool|mixed
     */
    public function getWindDirectionTextCountInInterval($year, $month = NULL, $day = NULL)
    {
        $where = " AND YEAR(TS) = $year";
        if ($month !== NULL) {
            $where .= " AND MONTH(TS) = $month";
        }
        if ($day !== NULL) {
            $where .= " AND DAY(TS) = $day";
        }

        $sql = "SELECT wind_direction_text, COUNT(wind_direction_text) AS value_occurence  
                FROM ".self::TABLE." 
                WHERE relative_Pressure >= 850 AND relative_Pressure <= 1300 AND wind_direction_text <> '0'
                $where 
                GROUP BY wind_direction_text";
        return $this->getSqlResult($sql, true);
    }


    /**
     * Seskupi po hodinach vypoctene hodnoty aggregations
     * @param array $columns
     * @param $year
     * @param $month
     * @param $day
     * @return array|bool|mixed
     */
    public function getColumnAgrByHour(array $columns, $year, $month, $day)
    {
        $where = " AND YEAR(TS) = $year";
        if ($month !== NULL) {
            $where .= " AND MONTH(TS) = $month";
        }
        if ($day !== NULL) {
            $where .= " AND DAY(TS) = $day";
        }

        $functions = [];
        foreach ($columns as $column => $settings) {
            foreach ($settings as $fce => $alias) {
                $functions[] = $fce.'('.$column.') AS '.$alias;
            }
        }
        $functionsString = implode(', ', $functions);
        $sql = "SELECT $functionsString, HOUR(TS) AS myhour
                FROM ".self::TABLE." 
                WHERE relative_Pressure >= 850 AND relative_Pressure <= 1300 
                $where 
                GROUP BY HOUR(TS)
                ORDER BY myhour";
        return $this->getSqlResult($sql, true);
    }
}