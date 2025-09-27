<?php

namespace App\Core\Database;

/**
 * BpDatabase class
 * @author Lutvi <lutvip19@gmail.com>
 */
class BpDatabase extends BpQuery 
{
    
    public function __construct(PDO $pdo = null) {
        parent::__construct($pdo);
    }

    public function setParameter(array $params) {
        $this->params = parent::addParam($params);
        return $this;
    }

    public function getOneOrNullResult() {
        $query = parent::query;
        $result = $this->setQuery($query ." LIMIT 1")->execute();
        
        return $result['result'] ?? null;
    }

    public function execute() {
        $result = parent::get();
        return $result;
    }
}