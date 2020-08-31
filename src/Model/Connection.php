<?php


namespace Naran\Axis\Model;


use Exception;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\QueryException;
use wpdb;


class Connection extends MySqlConnection
{
    /**
     * The active PDO connection.
     *
     * @var wpdb
     */
    protected $pdo;

    public function getPdo()
    {
        return $this;
    }

    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run(
            $query,
            $bindings,
            function ($query, $bindings) {
                if ($this->pretending()) {
                    return [];
                }

                $query  = $this->bindParams($query, $bindings);
                $result = $this->pdo->get_results($query);

                if ($result === false || $this->pdo->last_error) {
                    throw new QueryException($query, $bindings, new Exception($this->pdo->last_error));
                }

                return $result;
            }
        );
    }

    public function statement($query, $bindings = [])
    {
        $this->run(
            $query,
            $bindings,
            function ($query, $bindings) {
                if ($this->pretending()) {
                    return true;
                }

                $newQuery = $this->bindParams($query, $bindings, true);

                return $this->unprepared($newQuery);
            }
        );
    }

    public function affectingStatement($query, $bindings = [])
    {
        $this->run(
            $query,
            $bindings,
            function ($query, $bindings) {
                if ($this->pretending()) {
                    return 0;
                }

                $newQuery = $this->bindParams($query, $bindings, true);
                $result   = $this->pdo->query($newQuery);

                if ($result === false || $this->pdo->last_error) {
                    throw new QueryException($newQuery, $bindings, new Exception($this->pdo->last_error));
                }

                return intval($result);
            }
        );
    }

    public function unprepared($query)
    {
        return $this->run(
            $query,
            [],
            function ($query) {
                $result = $this->pdo->query($query);

                return ($result === false || $this->pdo->last_error);
            }
        );
    }

    /**
     * Return the last insert id
     *
     * @param string $args
     *
     * @return int
     * @noinspection PhpUnusedParameterInspection
     */
    public function lastInsertId($args)
    {
        return $this->pdo->insert_id;
    }

    /**
     * @param string $query
     * @param        $bindings
     * @param false  $update
     *
     * @return string|string[]
     * @noinspection PhpUnusedParameterInspection
     */
    private function bindParams($query, $bindings, $update = false)
    {
        $query    = str_replace('"', '`', $query);
        $bindings = $this->prepareBindings($bindings);

        if ( ! $bindings) {
            return $query;
        }

        $bindings = array_map(
            function ($replace) {
                if (is_string($replace)) {
                    $replace = "'" . esc_sql($replace) . "'";
                } elseif ($replace === null) {
                    $replace = "null";
                }

                return $replace;
            },
            $bindings
        );

        $query = str_replace(array('%', '?'), array('%%', '%s'), $query);
        $query = vsprintf($query, $bindings);

        return $query;
    }
}