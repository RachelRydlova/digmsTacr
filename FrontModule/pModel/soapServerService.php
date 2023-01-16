<?php
namespace XmlModule;

use A2bModels, Nette;

/**
 * Description of soapServerService
 *
 * @author PC
 */
class soapServerService extends Nette\Object{

    CONST statusOK = 200;

    CONST statusNotAllowed = 403;

    CONST statusOverRange  = 416;
    
    CONST statusOverTime    = 408;

    CONST messageNotAllowed = 'NOT ALLOWED';

    CONST messageOK         = 'OK';
    
    CONST messageOverTime   = 'GET SLOWER, TO FAST REQUEST';

    /**
     *
     * @param Nette\Http\Request $http
     */
    public $http;

    /**
     *
     * @var Nette\Http\Response
     */
    protected $httpResponse;

    /**
     *
     * @var string
     */
    public $remoteIP;

    /**
     *
     * @var A2bModels\connectLocal
     */
    protected $local;

    /**
     *
     * @var \A2bModels\karatFactory
     */
    protected $karat;

    /**
     *
     * @var \A2bModels\appInfo
     */
    protected $app;


    /**
     *
     * @param Nette\Http\Request $http
     * @param Nette\Http\Response $response
     * @param A2bModels\connectLocal $local
     * @param A2bModels\karatFactory $karat
     * @param \A2bModels\appInfo $app
     */
    public function __construct(Nette\Http\Request $http, Nette\Http\Response $response, A2bModels\connectLocal $local,
                                A2bModels\karatFactory $karat, \A2bModels\appInfo $app ) {
        $this->http     = $http;
        $this->remoteIP = $this->http->getRemoteAddress();
        $this->local    = $local;
        $this->karat    = $karat;
        $this->httpResponse = $response;
        $this->app      = $app;
    }//end function __construct



    /**
     * vrati seznam dostupnych vyrobcu v poli id_skupiny (vyrobce), nazev_vyrobce
     * @param string $partnerID
     * @return array(id_skupiny,nazev_vyrobce)
     */
    public function getProducer($partnerID){
        if ($this->verifyPartner($partnerID)){
            return $this->okReturn($this->karat->loadProducer());
        }else{
            return $this->falseReturn(self::statusNotAllowed,  self::messageNotAllowed);
        }//endif za verifyPartner
    }//end function getProducer.........................................................................................
    
    /**
     * vrati katalog kategorii produktu pro vytvoreni stromove struktury vyrobku
     * @param string $partnerID
     * @return array(id_katalog,nazev_katalog)
     */
    public function getCatalogs($partnerID){
        if ($this->verifyPartner($partnerID)){
            return $this->okReturn($this->karat->loadCatalogs());
        }else{
            return $this->falseReturn(self::statusNotAllowed,  self::messageNotAllowed);
        }//endif za verifyPartner
    }//end function getProducer.........................................................................................


    /**
     * vrati vyrobky patrici do pozadovane kategorie katalogu
     * @param string $partnerID
     * @param string $id_katalog
     * @return array(id_nomen, nazev)
     */
    public function getCatalogNomens($partnerID,$id_katalog){
        if ($this->verifyPartner($partnerID)){
            dd($this->app->getRequestTS($partnerID),'request time');
            dd(time(),'act time');
            if ((time()-$this->app->getRequestTS($partnerID)) > $this->app->getConf('catalogMinTime')){
                $this->app->setRequestTS($partnerID);
                return $this->okReturn($this->karat->loadCatalogNomens($id_katalog));
            }else{
                return $this->falseReturn (self::statusOverTime, self::messageOverTime);
            }
        }else{
            return $this->falseReturn(self::statusNotAllowed,  self::messageNotAllowed);
        }
    }//end function getCatalogNomens....................................................................................

    /**
     * zjisti aktualni skladove zasoby pro pozadovanou nomenklaturu, nebo seznam nomenklatur
     * @param string $partnerID
     * @param mixed $nomens - array or string
     * @return array(id_nomen,stav)
     */
    public function getNomensStock($partnerID, $nomens){
        if ($this->verifyPartner($partnerID)){
             if (is_array($nomens)){
                if (count($nomens) > $this->app->getConf('maxStockCount')){
                    return $this->falseReturn(self::statusOverRange, 'Max accepted nomens count is: '.$this->app->getConf('maxStockCount'));
                }
            }
            return $this->okReturn($this->karat->getNomensStock($nomens));
        }else{
            return $this->falseReturn(self::statusNotAllowed,  self::messageNotAllowed);
        }
    }//end function getCatalogNomens....................................................................................

    /**
     * vrati seznam parametru pro nomenklaturu jednu nomenklaturu
     * @param string $partnerID
     * @param string $nomen
     * @return array(paramName=>array(paramValues))
     */
    public function getParams($partnerID, $nomen){
        if ($this->verifyPartner($partnerID)){
            return $this->okReturn($this->karat->getParams($nomen));
        }else{
            return $this->falseReturn(self::statusNotAllowed,  self::messageNotAllowed);
        }
    }//end function getCatalogNomens....................................................................................
    
    
    /**
     * vrati dostupne obrazky pro jednu nomenklaturu
     * @param string $partnerID
     * @param string $nomen
     * @return array(paramName=>array(paramValues))
     */
    public function getPictures($partnerID, $nomen){
        if ($this->verifyPartner($partnerID)){
            return $this->okReturn($this->karat->getPictures($nomen));
        }else{
            return $this->falseReturn(self::statusNotAllowed,  self::messageNotAllowed);
        }
    }//end function getCatalogNomens....................................................................................

    /**
    * vrati aktualni cenu vyrobku nebo seznamu vyrobku - nomenklatur(y)
    * vrati cenu pro nomenklaturu, nebo seznam nomenklatur
    * @param type $partnerID
    * @param mixed $id_nomens - array, or string
    * @return array (id_nomen, price)
    */
   public function getPrice($partnerID, $id_nomens){
        if ($this->verifyPartner($partnerID)){
            if (is_array($id_nomens)){
                if (count($id_nomens) > $this->app->getConf('maxPriceCount')){
                    return $this->falseReturn(self::statusOverRange, 'Max accepted nomens count is: '.$this->app->getConf('maxPriceCount'));
                }
            }
            return $this->okReturn($this->karat->getPrice($id_nomens, $partnerID));
        }else{
            return $this->falseReturn(self::statusNotAllowed,  self::messageNotAllowed);
        }//endif za verifyPartner
   }//end function getPrice.............................................................................................


   
    /**
     *
     * @param array $x
     * @return array
     */
    public function baseTest($x) {
        $return = array();

        //$ip = '13.12.12.11';
        foreach ($x as $value){
            $return[] = $value.'-'.$this->remoteIP.' remote from construct: '.$this->remoteIP;
        }
        return $this->okReturn($return);
    }//end function baseTest............................................................................................
    
    

    //------------------------------------------------servisni funkce-------------------------------------------------//
    
    /**
     *
     * @param type $partnerID
     */
    protected function verifyPartner($partnerID){
        $partner = $this->local->database->table('partner')->where(array('partnerID'=>$partnerID,'isActive'=>1))
                                         ->select('remoteIP')->fetch();
        if ($partner and $this->remoteIP == $partner->remoteIP){
            return TRUE;
        }else{
            a2bLog("Uzivatel nebyl verifikovan. PartnerID: $partnerID , remoteIP: $this->remoteIP ",'warning');
            return FALSE;
        }
    }//end function verifyParnter.......................................................................................

    
    
    /**
     * vrati falseReturn pro klienta
     */
    protected function falseReturn($status, $message){
        return (object)array('status'=>$status,'data'=>array(),'message'=>$message);
    }//.................................................................................................................

    /**
     * vrati ok vysledek volani funkce
     * @param mixed $data
     * @return type
     */
    protected function okReturn($data, $message = 'OK'){
        return (object)array('status'=>self::statusOK,'data'=>$data,'message'=>$message);
    }//end function okReturn

}//end class soapServerService..........................................................................................

?>
