<?php

namespace A2bModels;

use A2bModels,
    \Nette\Caching\Cache;

/**
 * report Pmodel - p model pro report presenter
 *
 * @author Ondra310
 */
class reportPmodel extends karatModel
 {
    
    
    /**
     * pole obsahujici ID pro jednotlive reporty do DB
     * @var array
     */
    public static $reportsID = array('1'=>'R01','2'=>'R02','3'=>'R05','4'=>'R04','5'=>'R05','6'=>'R06');


    /**
     *
     * @var connectKarat
     */
    public $karat;

    
    /**
     * karat PDO
     * @var \PDO 
     */
    protected $kPDO;
    
    /**
     *
     * @var connectLocal 
     */
    protected $local;
    
    /**
     *
     * @var A2bFunc\applCache 
     */
    protected $cache;

    //$returnCache = $this->cache->load($fullCacheKey);
    //$this->cache->save($fullCacheKey, $returnArr, array(\Nette\Caching\Cache::EXPIRE=>self::fullNomenExpire));
    /**
     * @param \A2bModels\connectLocal $local
     */
    public function __construct(connectLocal $local, connectKarat $karat, \A2bFunc\applCache $cache) {
        $this->local = $local;
        $this->karat = $karat;
        $this->kPDO = $this->karat->pdo;
        $this->cache = $cache;
    }//end function construct...........................................................................................
    
    
    /**
     * ziska datetime s nastavenym casem pro invalidaci cache
     * cache invaliduje zitrejsi den rano v 6 hodin
     * @return \DateTime
     */
    protected function getCacheExpirTime(){
        $expirTime = new \DateTime('tomorrow');
        $expirTime->modify('+6 hours');
        return $expirTime;
    }//end function getCacheExpirTime...................................................................................

        /**
    * vrati user DB ID pro login, suppert functin 
    * @param type $login
    * @return int
    */
    protected function getUserID($login){
        return $this->local->database->table('user')->select('id')->where(array('login'=>$login))->fetch()->id;
    }//end function getUserID...........................................................................................
    
    /**
     * 
     * @param string $userID - login uzivatele
     * @param array $report = cislo reportu do statickeho pole
     */
    public function getAvailSubReports($userID,$report){
        
        $userDbID = $this->getUserID($userID);
        return $this->local->database->table('user_is_allowed_for')
                                      ->select('subreport.popis, subreport.trasa_id')
                                      ->where(array('user_id'=>$userDbID, 'subreport.reports_id'=>self::$reportsID[$report]))
                                      ->fetchAll();
                
    }//end function getAvailSubReports01................................................................................
    
        
    /**
     * data pro report01
     * @param string $trasa - trasa pro kterou se ma report generovat
     */
    public function getReport01ForTrasa($trasa){
        $cacheKey = self::$reportsID[1].'-'.$trasa;
        $cacheLoaded = $this->cache->load($cacheKey);
        if ($cacheLoaded) return $cacheLoaded;
        a2bWatchStart('report01');
        if ($this->kPDO){
            a2bWatchStart('report01');
            $sqlStr = "
                        SELECT
                            1 AS razeni /* :NV*/,
                            'Celkem' AS Pilir /* :HPilíř */,

                            SUM(dba.user_vydeje_skup_vap.castka) AS Castka /* :F##,###,##0 :HAktuální částka */,
                            dba.user_plan.pilir_all AS Cil /* :F##,###,##0 :HCíl měsíce*/,
                            round((SUM(dba.user_vydeje_skup_vap.castka) - dba.user_plan.pilir_all) / dba.user_plan.pilir_all * 100 ,2) AS Zbyva_proc /* :F##,###,##0.0 :HZbývá v %*/,
                            SUM(dba.user_vydeje_skup_vap.castka) - AVG(dba.user_plan.pilir_all) AS Rozdil /* :F##,###,##0 :HRozdíl v Kč*/

                            FROM
                            dba.user_vydeje_skup_vap
                            INNER JOIN dba.user_plan ON (dba.user_vydeje_skup_vap.rok = dba.user_plan.rok)
                            AND (dba.user_vydeje_skup_vap.mesic = dba.user_plan.mesic)
                            INNER JOIN dba.parrel ON (dba.user_vydeje_skup_vap.id_dodaci = dba.parrel.ID_PARTNERA)
                            AND (dba.parrel.SKUPINA = dba.user_plan.trasa)
                            WHERE
                            (dba.user_plan.rok = datepart(yyyy, GETDATE()) AND
                            dba.user_plan.mesic = datepart(mm, GETDATE()) AND
                            dba.user_plan.trasa = '$trasa' ) or
                            (dba.user_plan.rok = datepart(yyyy, GETDATE()) AND
                            dba.user_plan.mesic = datepart(mm, GETDATE()-1) AND
                            dba.user_plan.trasa = '$trasa')
                            GROUP BY
                            dba.user_plan.trasa,
                            dba.user_plan.mesic,
                            dba.user_plan.pilir_all
                            ORDER BY Zbyva_proc DESC

                        ";
            $result = $this->kPDO->query($sqlStr);
            if ($result){
                $zaznam = $result->fetchAll(\PDO::FETCH_OBJ);
                a2bWatchStop('report01');
                $this->cache->save($cacheKey, $zaznam, array(Cache::EXPIRE => $this->getCacheExpirTime()->getTimestamp()));
                a2bWatchStop('report01');
                return $zaznam;
            }else{
              $this->errorHandler($this->karat->pdo,$sqlStr,__METHOD__);
            }//endif za result
        }//endif za kPDO
        return FALSE;
    }//end function getNomenPrices......................................................................................
    
    /**
     * data pro report02
     * @return mixex array or FALSE on Failure
     */
    public function getReport02(){
        $cacheKey = self::$reportsID[2];
        $cacheLoaded = $this->cache->load($cacheKey);
        if ($cacheLoaded) return $cacheLoaded;
        dd('nacitam model');
        if ($this->kPDO){
            a2bWatchStart('report02');
            $trasy = $this->local->pdo->query('SELECT id, trasa_popis FROM trasa')->fetchAll(\PDO::FETCH_KEY_PAIR);
            $inTrasy = $this->getStringForIN(array_keys($trasy));
            dd($inTrasy,'trasy');
            $sqlStr = "
                        SELECT
                        1 AS razeni /* :NV*/,
                        dba.user_plan.trasa AS Trasa,
                        'Celkem' AS Pilir/* :HPilíř */,

                        round((SUM(dba.user_vydeje_skup_vap.castka) - dba.user_plan.pilir_all) / dba.user_plan.pilir_all * 100 ,2) AS Zbyva_proc/* :AR :F##,###,##0.0 :HZbývá v %*/

                        FROM
                        dba.user_vydeje_skup_vap
                        INNER JOIN dba.user_plan ON (dba.user_vydeje_skup_vap.rok = dba.user_plan.rok)
                        AND (dba.user_vydeje_skup_vap.mesic = dba.user_plan.mesic)
                        INNER JOIN dba.parrel ON (dba.user_vydeje_skup_vap.id_dodaci = dba.parrel.ID_PARTNERA)
                        AND (dba.parrel.SKUPINA = dba.user_plan.trasa)
                        WHERE
                        (dba.user_plan.rok = datepart(yyyy, GETDATE()) AND
                        dba.user_plan.mesic = datepart(mm, GETDATE()) AND
                        dba.user_plan.trasa IN ($inTrasy) ) or
                        (dba.user_plan.rok = datepart(yyyy, GETDATE()) AND
                        dba.user_plan.mesic = datepart(mm, GETDATE()-1) AND
                        dba.user_plan.trasa IN ($inTrasy) )
                        GROUP BY
                        dba.user_plan.trasa,
                        dba.user_plan.mesic,
                        dba.user_plan.pilir_all
                        ORDER BY Zbyva_proc DESC

                        ";
            $result = $this->kPDO->query($sqlStr);
            dd($result,'result');
            if ($result){
                $zaznam = $result->fetchAll(\PDO::FETCH_OBJ);
                a2bWatchStop('report02');
                $this->cache->save($cacheKey, $zaznam, array(Cache::EXPIRE => $this->getCacheExpirTime()->getTimestamp()));
                return $zaznam;
            }else{
              $this->errorHandler($this->karat->pdo,$sqlStr,__METHOD__);
            }//endif za result
        }//endif za kPDO
        return FALSE;
    }//end function getNomenPrices......................................................................................
    
    /**
     * data pro report01
     * @param string $trasa - trasa pro kterou se ma report generovat
     */
    public function getReport04ForTrasa($trasa){
        $cacheKey = self::$reportsID[4].'-'.$trasa;
        $cacheLoaded = $this->cache->load($cacheKey);
        if ($cacheLoaded) return $cacheLoaded;
        dd('nacitam model');
        if ($this->kPDO){
            a2bWatchStart('report04');
            
            $sqlStr = "
                        SELECT
                        1 AS poradi/*:Scisla.num:NV*/,
                        ICO,
                        Nazev,
                        Mesto,
                        jeFakturacni,
                        Loni /*:SLoni:F##,###,##0 :HLoni */,
                        Letos /*:SLetos:F##,###,##0 :HLetos */,
                        (Letos-Loni) AS Rozdil/*:SRozdil:F##,###,##0 :HRozdíl */
                        FROM
                        dba.w_AAA_zakaznici5_trasa_loni_letos
                        WHERE
                        TRASA = '$trasa' 
                        ORDER BY ICO, loni DESC

                        ";
            $result = $this->kPDO->query($sqlStr);
            if ($result){
                $zaznam = $result->fetchAll(\PDO::FETCH_OBJ);
                a2bWatchStop('report04');
                $this->cache->save($cacheKey, $zaznam, array(Cache::EXPIRE => $this->getCacheExpirTime()->getTimestamp()));
                return $zaznam;
            }else{
              $this->errorHandler($this->karat->pdo,$sqlStr,__METHOD__);
            }//endif za result
        }//endif za kPDO
        return FALSE;
    }//end function getNomenPrices......................................................................................
    
    
    /**
     * data pro report06
     * @return mixex array or FALSE on Failure
     */
    public function getReport06(){
        $cacheKey = self::$reportsID[6];
        $cacheLoaded = $this->cache->load($cacheKey);
        if ($cacheLoaded) return $cacheLoaded;
        if ($this->kPDO){
            a2bWatchStart('report05');
            
            $sqlStr = "
                       SELECT
                        dba.nom_skup.NAZEV_SKUPINY /* :AL:HSkupina */,
                        dba.nomenklatura.CISLO_NOMENKLATURY,
                        dba.nomenklatura.NAZEV AS Nazev/* :AL:HNázev zboží*/,
                        CONVERT(varchar(10), dba.nomenklatura.user_zmenanazvu, 102) AS DatumVzniku/* :AC:HDatum změny*/
                        FROM
                        dba.nom_skup
                        INNER JOIN dba.nomenklatura ON (dba.nom_skup.ID_SKUPINY = dba.nomenklatura.ID_SKUPINY)
                        WHERE
                        user_zmenanazvu >= getdate() -21 AND
                        PLATNOST = 1 AND
                        TYP = 0 AND
                        user_datum_vzniku <> user_zmenanazvu
                        ORDER BY DatumVzniku DESC, Nazev

                        ";
            $result = $this->kPDO->query($sqlStr);
            
            if ($result){
                $zaznam = $result->fetchAll(\PDO::FETCH_OBJ);
                a2bWatchStop('report05');
                $this->cache->save($cacheKey, $zaznam, array(Cache::EXPIRE => $this->getCacheExpirTime()->getTimestamp()));
                return $zaznam;
            }else{
              $this->errorHandler($this->karat->pdo,$sqlStr,__METHOD__);
            }//endif za result
        }//endif za kPDO
        return FALSE;
    }//end function getNomenPrices......................................................................................
    
   /**
     * testovaci dataSource pro grid05
     * @return array
     */
    public function getDataForGrid05($filter = NULL, $order = NULL){
        $fKey = $oKey = $fStr = $oStr = '';//init
        $likeCol = array('CISLO_NOMENKLATURY'=>'LIKE', 'NAZEV'=>'like');
        if ($filter){
            $fKey = json_encode($filter);
            foreach ($filter as $col=>$value){
                if (isset($likeCol[$col]))//pokud je v like col, tak se provede vyhledani podle like
                     $fStr.= "AND $col LIKE '%$value%' ";
                else $fStr.= "AND $col = '$value' ";
            }
        }
        if ($order){
            $oStr = $order[0].' '.$order[1];
        }else{
           $oStr = 'DatumVzniku DESC, Nazev';
        }
        $cacheKey = $oStr.$fKey;
        $cacheLoaded = $this->cache->load($cacheKey);
        if ($cacheLoaded) return $cacheLoaded;
        
        $sqlStr = "SELECT
                        dba.nom_skup.NAZEV_SKUPINY ,
                        dba.nomenklatura.CISLO_NOMENKLATURY,
                        dba.nomenklatura.NAZEV,
                        CONVERT(varchar(10), dba.nomenklatura.user_zmenanazvu, 102) AS DatumVzniku
                        FROM
                        dba.nom_skup
                        INNER JOIN dba.nomenklatura ON (dba.nom_skup.ID_SKUPINY = dba.nomenklatura.ID_SKUPINY)
                        WHERE
                        user_zmenanazvu >= getdate() -21 AND
                        PLATNOST = 1 AND
                        TYP = 0 AND
                        user_datum_vzniku <> user_zmenanazvu
                        $fStr
                        ORDER BY $oStr";
        
        $result = $this->kPDO->query($sqlStr);
        if ($result){
            $zaznam = $result->fetchAll(\PDO::FETCH_OBJ);
            $this->cache->save($cacheKey, $zaznam, array(Cache::EXPIRE => $this->getCacheExpirTime()->getTimestamp()));
            return $zaznam;
        }else{
            $this->errorHandler ($this->kPDO,$sqlStr,__METHOD__);
        }
        
        return array();
    }//end function getDataForGrid05....................................................................................
    
    /**
     * vybere list dostupnych skupiny pro grid 05
     * @return array
     */
    public function getSkupinyForGrid05(){
        $skupiny = NULL;
        
        $cacheKey = 'skupinyGrid05';
        $cacheLoaded = $this->cache->load($cacheKey);
        if ($cacheLoaded) return $cacheLoaded;
        
        $result = $this->kPDO->query('SELECT
                        rtrim(dba.nom_skup.NAZEV_SKUPINY), rtrim(dba.nom_skup.NAZEV_SKUPINY)
                        
                        FROM
                        dba.nom_skup
                        INNER JOIN dba.nomenklatura ON (dba.nom_skup.ID_SKUPINY = dba.nomenklatura.ID_SKUPINY)
                        WHERE
                        user_zmenanazvu >= getdate() -21 AND
                        PLATNOST = 1 AND
                        TYP = 0 AND
                        user_datum_vzniku <> user_zmenanazvu
                        GROUP BY dba.nom_skup.ID_SKUPINY, dba.nom_skup.NAZEV_SKUPINY
                        ');
        if ($result){
            $skupiny = $result->fetchAll(\PDO::FETCH_KEY_PAIR);
            $this->cache->save($cacheKey, $skupiny, array(Cache::EXPIRE => $this->getCacheExpirTime()->getTimestamp()));
        }else{
            $this->errorHandler ($this->kPDO);
        }
       
        return $skupiny;
    }//end function getDataForGrid05....................................................................................
            

    
}//end class reportPmodel...............................................................................................
?>