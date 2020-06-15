<?php
    class DBAcess {
        //octavian
        private static $instance = null;
        private static $mysql = null;
        private function __construct()
        {
            if (mysqli_connect_errno()) {
                die ('Conexiunea a esuat...');
            }
            self::$mysql = new mysqli (
                                       'localhost', // locatia serverului (aici, masina locala)
                                       'root',       // numele de cont
                                       '',    // parola (atentie, in clar!)
                                       'terrordb'   // baza de date
                                       );
        }
        
        public static function getInstance()
        {
            if (self::$instance == null)
            {
                self::$instance = new DBAcess();
            }
            return self::$instance;
        }
        
        //creates the ATTACKS table, having columns names as given in the $headersArray
        public function createTerrorTable($headersArray) {
            $queryHeaders = "";
            foreach($headersArray as $columnName){
                $queryHeaders = $queryHeaders. " " .$columnName. " TEXT,";
            }
            $queryHeaders = substr($queryHeaders, 0, -1);
            
            if(!self::$mysql -> query('CREATE TABLE ATTACKS ('.$queryHeaders.')')) {
                printf("%s",self::$mysql->error);
            }
        }
        
        //adds an attak to the attaks table
        //@param $attackDataMap, an array acting as a map, mapping the each column name with a value,
        //where the key value(array index) should be a string representing the columnName and the value
        //should be a string representing the value to be added at the respective column
        public function addAttack(array $attakDataMap) {
            foreach ($attakDataMap as $key => $value) {
                if((strpos($key, '\'') !== false || strpos($value, '\'') !== false)) {
                    echo $key."".$value."not alphanumeric";
                    return;
                }
            }
            $columnsToInsert = '';
            $valuesToInsert = '';
            foreach ($attakDataMap as $key => $value) {
                $columnsToInsert = $columnsToInsert."".$key.",";
                $valuesToInsert = $valuesToInsert."'".$value."',";
            }
            $columnsToInsert = substr($columnsToInsert, 0, -1);
            $valuesToInsert = substr($valuesToInsert, 0, -1);
            echo 'INSERT INTO ATTACKS('.$columnsToInsert.') VALUES('.$valuesToInsert.')';
            if(!self::$mysql -> query('INSERT INTO ATTACKS('.$columnsToInsert.') VALUES('.$valuesToInsert.')')) {
                printf("%s",self::$mysql->error);
            }
        }
        
        public function getAvailableFilterValuesAsJson($filtersDict) {
            $res = self::getAttacksByFilters($filtersDict);
            return self::mapPosibileFiltersValuesToJson($res);
        }
        
        public function getAllAvailableFilters() {
            $res = self::selectAll();
            return self::mapPosibileFiltersValuesToJson($res);
        }
        
        
        public function getAttacksByFilters($filtersDict, $limit = null) {
            $whereClause = "WHERE";
            $isInitial = 1;
            foreach($filtersDict as $key => $value) {
                if($key == "intervals") {
                    foreach($value as $intervalFilter => $intervalArray) {
                        foreach($intervalArray as $intervalFieldName => $intervalEndpoints) {
                            foreach($intervalEndpoints as $index => $intervalEndpoint) {
                                if($index % 2 == 0) {
                                    if($whereClause == "WHERE") {
                                        $whereClause = $whereClause." ".'CAST('.$intervalFieldName.' AS DECIMAL(50))'.' BETWEEN '.$intervalEndpoint.'';
                                        $isInitial = 0;
                                    } else {
                                        $whereClause = $whereClause." OR ".'CAST('.$intervalFieldName.' AS DECIMAL(50))'.' BETWEEN '.$intervalEndpoint.'';
                                    }
                                } else {
                                    $whereClause = $whereClause." AND ".$intervalEndpoint;
                                }
                            }
                        }
                    }
                } else {
                    var_dump($value);
                        if($whereClause == "WHERE") {
                            $whereClause = $whereClause." ".$key." LIKE '".$value."'";
                        } else {
                            $whereClause = $whereClause." OR ".$key." LIKE '".$value."'";
                        }
                }
            }
            $res = null;
            if($limit == null) {
                $res = self::$mysql->query('SELECT * FROM ATTACKS '.$whereClause);
            } else {
                $res = self::$mysql->query('SELECT * FROM ATTACKS '.$whereClause.' LIMIT '.$limit.',3500');
            }
            echo'SELECT * FROM ATTACKS '.$whereClause;
            return $res;
        }
        
        public function getAttacksByFiltersAsJson($filtersDict, $limit = null) {
            $res = null;
            $res = self::getAttacksByFilters($filtersDict,$limit);
            return self::toJsonString($res);
        }
        
        
        public function selectAll($limit = null) {
            if($limit != null) {
                $res = self::$mysql->query('SELECT * FROM ATTACKS LIMIT '.$limit.',3500');
                return $res;
            }
            $res = self::$mysql->query('SELECT * FROM ATTACKS');
            return $res;
        }
        
        //octavian
        //selects all from the terror db, converts it to a json,
        //returns a string representing data as json
        public function selectAllAsJson($limit = null) {
            $query_results = self::selectAll($limit);
            return self::toJsonString($query_results);
        }
        
        
        //maria
        public function selectByCoord($country,$year,$weapon){
            if((strpos($weapon, '\'') !== false || strpos($year, '\'') !== false || strpos($country, '\'') !== false)) {
                echo "status 400 bad request";
                return;
            }
            if($country=='Any')
                $country='%';
            if($year=='Any')
                $year='%';
            if($weapon=='Any')
                $weapon='%';
            if(!self::$mysql -> query('SELECT country_txt,iyear,weaptype1_txt, latitude, longitude FROM ATTACKS WHERE country_txt LIKE "'.$country.'" AND iyear LIKE "'.$year.'" AND weaptype1_txt LIKE "'.$weapon.'"'))
                printf("%s",self::$mysql->error);
            else return self::$mysql->query('SELECT country_txt,iyear,weaptype1_txt, latitude, longitude FROM ATTACKS WHERE country_txt LIKE "'.$country.'" AND iyear LIKE "'.$year.'" AND weaptype1_txt LIKE "'.$weapon.'"');
        }
        
        //maria
        public function selectByCoordJson($country,$year,$weapon){
            if((strpos($weapon, '\'') !== false || strpos($year, '\'') !== false || strpos($country, '\'') !== false)) {
                echo "status 400 bad request";
                return;
            }
            $queryResult = self::selectByCoord($country,$year,$weapon);
            return self::toJsonString($queryResult);
        }
        
        //mircea
        //selects attacks between 2 nr
        public function selectBetween($nr,$country,$year,$weapon){
            if((strpos($weapon, '\'') !== false || strpos($year, '\'') !== false || strpos($country, '\'') !== false)) {
                echo "status 400 bad request";
                return;
            }
            if($country=='Any')
                $country='%';
            if($year=='Any')
                $year='%';
            if($weapon=='Any')
                $weapon='%';
            if(!self::$mysql -> query('SELECT country_txt,iyear,weaptype1_txt FROM ATTACKS  WHERE country_txt LIKE "'.$country.'" AND iyear LIKE "'.$year.'" AND weaptype1_txt LIKE "'.$weapon.'"'.'LIMIT '.$nr.',3500'))
                printf("%s",self::$mysql->error);
            else return self::$mysql->query('SELECT country_txt,iyear,weaptype1_txt FROM ATTACKS  WHERE country_txt LIKE "'.$country.'" AND iyear LIKE "'.$year.'" AND weaptype1_txt LIKE "'.$weapon.'"'.'LIMIT '.$nr.',3500');
            
        }
        
        
        //octavian
        //selects attacks between 2 nr
        //returns a string representin data as json
        public function selectBetweenJson($nr, $country, $year, $weapon) {
            if((strpos($weapon, '\'') !== false || strpos($year, '\'') !== false || strpos($country, '\'') !== false)) {
                echo "status 400 bad request";
                return;
            }
            $queryResult = self::selectBetween($nr, $country, $year, $weapon);
            return self::toJsonString($queryResult);
        }
        
        //mircea
        //selects total nr of attacks
        public function totalNr($country,$year,$weapon){
            if((strpos($weapon, '\'') !== false || strpos($year, '\'') !== false || strpos($country, '\'') !== false)) {
                echo "status 400 bad request";
                return;
            }
            if($country=='Any')
                $country='%';
            if($year=='Any')
                $year='%';
            if($weapon=='Any')
                $weapon='%';
            if(!self::$mysql -> query('SELECT COUNT(*) AS NR ,country_txt,iyear,weaptype1_txt FROM ATTACKS WHERE country_txt LIKE "'.$country.'" AND iyear LIKE "'.$year.'" AND weaptype1_txt LIKE "'.$weapon.'"'))
                printf("%s",self::$mysql->error);
            else return self::$mysql->query('SELECT COUNT(*) AS NR ,country_txt,iyear,weaptype1_txt FROM ATTACKS WHERE country_txt LIKE "'.$country.'" AND iyear LIKE "'.$year.'" AND weaptype1_txt LIKE "'.$weapon.'"');;
        }
        
        //maria
        //selects total nr of attacks
        //returns a string representin data as json
        public function totalNrJson($country,$year,$weapon){
            if((strpos($weapon, '\'') !== false || strpos($year, '\'') !== false || strpos($country, '\'') !== false)) {
                echo "status 400 bad request";
                return;
            }
            $queryResult = self::totalNr($country,$year,$weapon);
            return self::toJsonString($queryResult);
        }
        
        //mircea
        //select by country, year and weapon
        public function selectBy($country,$year,$weapon){
            if((strpos($weapon, '\'') !== false || strpos($year, '\'') !== false || strpos($country, '\'') !== false)) {
                echo "status 400 bad request";
                return;
            }
            if($country=='Any')
                $country='%';
            if($year=='Any')
                $year='%';
            if($weapon=='Any')
                $weapon='%';
            if(!self::$mysql -> query('SELECT country_txt,iyear,weaptype1_txt FROM ATTACKS WHERE country_txt LIKE "'.$country.'" AND iyear LIKE "'.$year.'" AND weaptype1_txt LIKE "'.$weapon.'"'))
                printf("%s",self::$mysql->error);
            else return self::$mysql->query('SELECT country_txt,iyear,weaptype1_txt FROM ATTACKS WHERE country_txt LIKE "'.$country.'" AND iyear LIKE "'.$year.'" AND weaptype1_txt LIKE "'.$weapon.'"');
        }
        
        //maria
        //select by country, year and weapon
        //returns a string representin data as json
        public function selectByJson($country,$year,$weapon){
            $queryResult = self::selectBy($country,$year,$weapon);
            return self::toJsonString($queryResult);
        }
        
        //mircea
        //get all countries
        public function getCountries(){
            if(!self::$mysql -> query('SELECT DISTINCT country_txt FROM ATTACKS ORDER BY country_txt'))
                printf("%s",self::$mysql->error);
            else return self::$mysql->query('SELECT DISTINCT country_txt FROM ATTACKS ORDER BY country_txt');
        }
        
        //maria
        //get all countries
        //returns a string representin data as json
        public function getCountriesJson(){
            $queryResult = self::getCountries();
            return self::toJsonString($queryResult);
        }
        
        //mircea
        //get all years
        public function getYears(){
            if(!self::$mysql -> query('SELECT DISTINCT iyear FROM ATTACKS ORDER BY iyear'))
                printf("%s",self::$mysql->error);
            else return self::$mysql->query('SELECT DISTINCT iyear FROM ATTACKS ORDER BY iyear');
        }
        
        //maria
        //get all years
        //returns a string representin data as json
        public function getYearsJson(){
            $queryResult = self::getYears();
            return self::toJsonString($queryResult);
        }
        
        //mircea
        //get all weapons
        public function getWeapons(){
            if(!self::$mysql -> query('SELECT DISTINCT weaptype1_txt FROM ATTACKS ORDER BY weaptype1_txt'))
                printf("%s",self::$mysql->error);
            else return self::$mysql->query('SELECT DISTINCT weaptype1_txt FROM ATTACKS ORDER BY weaptype1_txt');
        }
        
        //maria
        //get all weapons
        //returns a string representin data as json
        public function getWeaponsJson(){
            $queryResult = self::getWeapons();
            return self::toJsonString($queryResult);
        }
        //milea octavian
        // encodes a db query into a json, (key: int, value-> array of each db select result), and a pair(key: "size", value: "int"))
        // which represents the numberof results;
        private function toJsonString($fromQuery) {
            $resultsCount = 0;
            $resultsArrayMapping = array();
            while($attacks = $fromQuery -> fetch_assoc()) {
                $resultsArrayMapping[$resultsCount] = $attacks;
                $resultsCount = $resultsCount + 1;
            }
            $resultsArrayMapping["dataSz"] = $resultsCount;
            return json_encode($resultsArrayMapping);
        }
        
        private function mapPosibileFiltersValuesToJson($fromQuery) {
            $filtersOutputMap = array();
            $filtersArray = array();
            while($attack = $fromQuery -> fetch_assoc()) {
                foreach($attack as $column => $value) {
                    //if(!isset($filtersOutputMap[$column])) {
                      //  $filtersOutputMap[$column] = array();
                   // }
                    $filtersOutputMap[$value] = $column;
                }
            }
            return json_encode($filtersOutputMap);
        }
    }
    ?>
