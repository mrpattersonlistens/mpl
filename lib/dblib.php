<?php
/**
 * Database wrapper object
 * Facilitates communication with the database
 * Much easier than preparing and executing and error checking every query!
 */
defined('INTERNAL_SCRIPT') || die;
 
define('ALLOW_MISSING', 0);
define('MUST_EXIST', 1);
define('ALLOW_MULTIPLES', 2);
 
class database_wrapper {
    protected $host;
    protected $database;
    protected $user;
    protected $password;
    protected $connection;
    protected $actions = array ('create', 'read', 'update', 'delete');
    protected $reads = 0;
    protected $writes = 0;
    protected $querystarttime;
    protected $queriestime = 0;
    protected $tables;
    protected $columns = array();
     
    /**
     * Constructor
     * Given db info and credentials, establishes a PDO database connection
     * 
     * @param string $host The database host
     * @param string $database The name of the database
     * @param string $user
     * @param string $password 
     */
    function __construct($host, $database, $user, $password) {
        $this->host = $host;
        $this->database = $database;
        $this->user = $user;
        $this->password = $password;
        
        try {
            $this->connection = new PDO('mysql:host='.$this->host.';dbname='.$this->database, $this->user, $this->password);
        } catch (Exception $e) {
            throw new coding_exception('Error connecting to database: '.$e->getMessage());
        }
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    protected function start_query($action) {
        switch ($action) {
            case 'read':
                $this->reads++;
                break;
            case 'create':
            case 'update':
            case 'delete':
                $this->writes++;
        }
        $this->querystarttime = microtime(true);
    }
    
    protected function end_query() {
        $time = microtime(true) - $this->querystarttime;
        $this->queriestime = $this->queriestime + $time;
    }
    
    
    
    /**
     * Queries the DB and returns a single record object
     * 
     * @param string $query The SQL query with param placeholders
     * @param array $params An array of parameters
     * @param int $strictness What to do if no result or more than 1 result 
     *          Use ALLOW_MISSING, MUST_EXIST, ALLOW MULTIPLES
     * @param false | single record object
     */
    public function get_record($query, $params, $strictness = ALLOW_MISSING) {
        if (strpos($query, 'SELECT') !== 0) {
            throw new coding_exception('Use $DB->get_record ONLY to run SELECT statements.');
        }
        
        $this->start_query('read');
        $stmt = $this->prepare_execute($query, $params, 'read');
        
        $fetch = $stmt->fetchAll();
        if (!is_null($fetch) and $fetch === false) {
            // fetch failed
            $this->reads--;
            throw new database_read_exception('Failed to fetch results for query: ' . "\n" . $stmt->queryString);
        } else if (!count($fetch) and $strictness == MUST_EXIST) {
            // No results found
            throw new database_read_exception('No record found for query: ' . "\n" . $stmt->queryString);
        } else if (count($fetch) > 1 and $strictness != ALLOW_MULTIPLES) {
            // More than one result found
            throw new database_read_exception('More than one record found for query: ' . "\n" . $stmt->queryString);
        } else {
            $this->end_query();
            if (!count($fetch)) {
                return false;
            } else {
                return reset($fetch);
            }
        }
    }
    
    /**
     * Queries the DB and returns a single field from a record object
     */
    public function get_field($table, $field, $params = array(), $strictness = ALLOW_MISSING) {
        if (!is_array($params)) {
            throw new coding_exception('$params must be an array.');
        }
        if (!in_array($table, $this->get_tables(true))) {
            throw new coding_excpetion("Table $table not found in database.");
        }
        
        $select = "SELECT $field FROM $table";
        $queryparams = array();
        
        if (!empty($params)) {
            $where = " WHERE ";
            $i = 1; // There's at least one parameter
            foreach ($params as $param => $value) {
                $where .= "$param = ?";
                $queryparams[] = $value;
                if ($i < count($params)) {
                    $where .= " AND ";
                }
                $i++;
            }
        }
        
        $query = $select.$where;
        $record = $this->get_record($query, $queryparams, $strictness);
        return $record->$field;
    }
    
    /**
     * Queries the DB and returns an array of record object
     * 
     * @param string $query The SQL query with param placeholders
     * @param array $params An array of parameters
     * @return array of record objects
     */
    public function get_records($query, $params) {
        if (strpos($query, 'SELECT') !== 0) {
            throw new coding_exception('Use $DB->get_records ONLY to run SELECT statements.');
        }
        
        $this->start_query('read');
        $stmt = $this->prepare_execute($query, $params, 'read');
        
        $fetch = $stmt->fetchAll();
        if ($fetch === false) {
            // fetch failed
            $this->reads--;
            throw new database_read_exception('Failed to fetch results for query: ' . "\n" . $query);
        } else {
            $this->end_query();
            return $fetch;
        }
    }
    
    /**
     * Queries the DB and returns an array of record objects
     * Uses a sql statment with NO parameters
     * 
     * @param string $query The SQL query with param placeholders
     * @return array of record objects
     */
    public function get_all_records($query) {
        if (strpos($query, 'SELECT') !== 0) {
            throw new coding_exception('Use $DB->get_all_records ONLY to run SELECT statements.');
        }
        
        $this->start_query('read');
        if ($stmt = $this->connection->query($query)) {
            $fetch = $stmt->fetchAll();
            $this->end_query();
            if ($fetch === false) {
                // fetch failed
                $this->reads--;
                throw new database_read_exception('Failed to fetch results for query: ' . "\n" . $query);
            } else {
                return $fetch;
            }
        } else {
            // query failed
            throw new database_read_exception('Failed to fetch results for query: ' . "\n" . $query);
        }
    }
    
    /**
     * Runs a sql DELETE statement
     * 
     * @param string $query The SQL query with param placeholders
     * @param array $params An array of parameters
     * @return true for success
     */
    public function delete_record($query, $params = array()) {
        if (strpos($query, 'DELETE') !== 0) {
            throw new coding_exception('Use $DB->delete_record ONLY to run DELETE statements.');
        }
        
        $this->start_query('delete');
        if ($this->prepare_execute($query, $params, 'delete')) {
            $this->end_query();
            return true;
        } else {
            $this->writes--;
            return false;
        }
    }
    
    /**
     * Runs a sql INSERT statement
     * 
     * @param string $query The SQL query with param placeholders
     * @param array $params An array of parameters
     * @return int id of new record
     */
    public function insert_record($query, $params) {
        if (strpos($query, 'INSERT INTO') !== 0) {
            throw new coding_exception('Use $DB->insert_record ONLY to run INSERT statements.');
        }
        
        $this->start_query('create');
        if ($this->prepare_execute($query, $params, 'create')) {
            $this->end_query();
            return $this->connection->lastInsertId();
        } else {
            // insert failed
            $this->writes--;
            return false;
        }
    }
    
    /**
     * Runs a sql UPDATE statement
     * 
     * @param string $query The SQL query with param placeholders
     * @param array $params An array of parameters
     * @return true for success
     */
    public function update_record($query, $params) {
        if (strpos($query, 'UPDATE') !== 0) {
            throw new coding_exception('Use $DB->update_record ONLY to run UPDATE statements.');
        }
        
        $this->start_query('update');
        if ($this->prepare_execute($query, $params, 'update')) {
            $this->end_query();
            return true;
        }
    }
    
    public function set_field($table, $field, $value, $params = array()) {
        if ((!is_array($params)) or (array_keys($params) === range(0, count($arr) - 1))) {
            throw new coding_excpetion('$params must be an array of named params');
        }
        if (!in_array($table, $this->get_tables(true))) {
            throw new coding_excpetion("Table $table not found in database.");
        }
        
        $update = "UPDATE $table SET $field = :value";
        
        $queryparams = array(':value' => $value);
        if (!empty($params)) {
            $where = " WHERE ";
            $i = 1; // There's at least one parameter
            foreach ($params as $param => $value) {
                $where .= "$param = :$param";
                $queryparams[":$param"] = $value;
                if ($i < count($params)) {
                    $where .= " AND ";
                }
                $i++;
            }
        }
        
        $query = $update.$where;
        $this->update_record($query, $queryparams);
    }
    
    /**
     * Prepare and execute a sql statement
     * 
     * @param string $query The SQL query with param placeholders
     * @param array $params An array of parameters
     * @return PDO statement object
     */
    private function prepare_execute($query, $params, $action) {
        if (!is_array($params)) {
            throw new coding_exception('$params must be an array.');
        }
        
        if (!in_array($action, $this->actions)) {
            throw new coding_exception('Bad action passed to $DB->prepare_execute.');
        }
        
        $exception = "database_{$action}_exception";
        
        try {
            $stmt = $this->connection->prepare($query);
        } catch (Exception $e) {
            throw new $exception('There was a problem preparing the SQL statement:' . "\n" . $query);
        }
        
        try {
            $stmt->execute($params);
        } catch (Exception $e) {
            debugging(pr($this->debuginfo($stmt)));
            throw new $exception('There was a problem executing the SQL statement:' . "\n" . $query);
        }
        
        return $stmt;
    }
    
    public function debuginfo($stmt) {
        ob_start();
        $stmt->debugDumpParams();
        $debuginfo = ob_get_clean();
        return $debuginfo;
    }
    
    /**
     * Returns the number of reads done by this database.
     * @return int Number of reads.
     */
    public function perf_get_reads() {
        return $this->reads;
    }
    
    /**
     * Returns the number of writes done by this database.
     * @return int Number of writes.
     */
    public function perf_get_writes() {
        return $this->writes;
    }
    
    /**
     * Time waiting for the database engine to finish running all queries.
     * @return float Number of seconds with microseconds
     */
    public function perf_get_queries_time() {
        return $this->queriestime;
    }
    
    public function get_tables($usecache = true) {
        if ($usecache and $this->tables !== null) {
            return $this->tables;
        }
        $this->start_query('read');
        $stmt = $this->connection->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'machina'");
        $tables = $stmt->fetchAll();
        $this->end_query();
        foreach ($tables as $table) {
            $tablename = $table->table_name;
            $this->tables[$tablename] = $tablename;
        }
        
        return $this->tables;
    }
    
    public function get_columns($table, $usecache = true) {
        if ($usecache and isset($this->columns[$table])) {
            return $this->columns[$table];
        }
        
        $this->start_query('read');
        $stmt = $this->connection->query("SELECT column_name FROM information_schema.columns WHERE table_schema = 'machina' AND table_name = '$table'");
        $columns = $stmt->fetchAll();
        $this->end_query();
        foreach ($columns as $column) {
            $columname = $column->column_name;
            $this->columns[$table][$columname] = $columname;
        }
        
        return $this->columns[$table];
    }
}
