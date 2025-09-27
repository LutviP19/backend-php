<?php

namespace App\Core\Database;

class BpDatabase extends QueryBuilder {
    
    public function __construct($table, PDO $connection = null) {
        parent::__construct($connection);

        parent::table = $table;
    }

    public function createQuery(string $sql) {
        $this->sql = parent::setSQL($sql);
        return $this;
    }

    public function setParameter(array $params) {
        $this->params = parent::setParams($params);
        return $this;
    }

    public function getOneOrNullResult() {
        $result = parent::first();
        return $result;
    }

    public function execute() {
        $result = parent::get();
        return $result;
    }
}