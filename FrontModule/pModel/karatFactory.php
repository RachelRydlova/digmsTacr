<?php

namespace A2bModels;

use A2bModels;

/**
 * karat factory, zastresuje operace s karatem pro soapServerService
 * @author     ondra310
 * @package    vapolDPrice
 * @copyright (c) 02/2013, ondra310
 */
class karatFactory extends A2bModels\pModelBase {

    
    /**
     *  @var A2bModels\connectKarat
     */
    protected $karat;
    
    /**
     *
     * @var A2bModels\connectLocal
     */
    protected $connectLocal;
    

    /**
     *
     * @param connectLocal $orm
     * @param connectTecDoc $tecDoc
     * @param connectKarat $karatService 
     */
    public function __construct(A2bModels\connectLocal $local, A2bModels\connectKarat $karatService){
	$this->karat = $karatService;
	$this->local = $local;
        }

   /**
    * @return A2bModels\connectKarat
    */    
   public function getKaratService(){
       return $this->karatService;
   }//end function getKaratService......................................................................................

   /**
    * nacte vyrobce
    * @return array
    */
   public function loadCatalogs(){
       $karatSkupiny = new \A2bModels\karatStrom($this->karat);
       return $karatSkupiny->loadStromList();
   }//end function loadProducer.........................................................................................

   /**
    *
    * @param string $id_katalog
    */
   public function loadCatalogNomens($id_katalog){
       $retArr = array();
       $karatNomen = new A2bModels\karatNomen($this->karat);
       $nomens = $karatNomen->loadNomenListForImport($id_katalog, 'prod_skupina');
       if ($nomens){
           foreach ($nomens AS $value){
               $retArr[] = array('id_nomen'=>$value['CISLO_NOMENKLATURY'],
                                 'nazev'       =>$value['NAZEV']
                                 );
           }//end foreach
       }//end nomens
       return $retArr;
   }//end function loadCatalogNomens....................................................................................


   /**
    * nacte vyrobce
    * @return array
    */
   public function loadProducer(){
       $karatSkupiny = new \A2bModels\karatSkupiny($this->karat);
       return $karatSkupiny->loadSkupiny();
   }//end function loadProducer.........................................................................................

   /**
    * nacte ceny pro nomenklaturu(y)
    * @param mixed $nomens - array or string
    * @param string $partnerID - ID partnera
    * @return array (id_nomen, cena)
    */
   public function getPrice($nomens, $partnerID){
       $karatPrice = new A2bModels\karatPrice($this->karat);
       $karatNomen = new A2bModels\karatNomen($this->karat);

       $pocetVstup = (is_array($nomens))?count($nomens):1;
       $idNomens = $karatNomen->getNomensID($nomens);
       if ($idNomens and $pocetVstup == count($idNomens)){
           $price = $karatPrice->getPriceByList($partnerID, $idNomens, 1);
       }else{//nepodarilo se nacist nomenklatury
           a2bLog('Nezdarilo se nacist id_nomenklatur, nebo si pocty neodpovidaji','error');
           return FALSE;
       }//endif za
       
       $retArray = array();//init
       if ($price){
           foreach ($price as $key=>$value){
               $retArray[] = array('id_nomen'=>$key,'cena'=>$value['cena_out']);
           }
       }//endif za price
       return $retArray;
   }//end function loadProducer.........................................................................................


   /**
    * zjisti skladovou zasobu pro nomenklaru(y)
    * @param mixed $nomens - array or string
    * @return array (id_nomen, stav)
    */
   public function getNomensStock($nomens){
       $karatPrice = new A2bModels\karatPrice($this->karat);
       $karatNomen = new A2bModels\karatNomen($this->karat);

       $pocetVstup = (is_array($nomens))?count($nomens):1;
       $idNomens = $karatNomen->getNomensID($nomens);
       if ($idNomens and $pocetVstup == count($idNomens)){
           $stock = $karatPrice->getAvail($idNomens);
       }else{//nepodarilo se nacist nomenklatury
           a2bLog('Nezdarilo se nacist id_nomenklatur, nebo si pocty neodpovidaji','error');
           return FALSE;
       }//endif za

       $retArray = array();//init
       if ($stock){
           foreach ($stock as $key=>$value){
               $retArray[] = array('id_nomen'=>$key,'stav'=>$value);
           }
       }//endif za price
       return $retArray;
   }//end function loadProducer.........................................................................................


    /**
    * nacte parametry pro nomenklaturu(y)
    * @param mixed $nomens - array or string
    * @return array (paramName, array(paramValue))
    */
   public function getParams($nomen){
       $karatParam = new A2bModels\karatParametry($this->karat);
       $karatNomen = new A2bModels\karatNomen($this->karat);
       $idNomens = $karatNomen->getNomensID($nomen);
       $retArray = $karatParam->loadParamForNomen(key($idNomens));
       return $retArray;
   }//end function loadProducer.........................................................................................
   
   /**
    * nacte obrazky pro nomenklaturu
    * @param string $nomens
    * @return array(array(file_url))
    */
   public function getPictures($nomen){
       $karatParam = new A2bModels\karatParametry($this->karat);
       $karatNomen = new A2bModels\karatNomen($this->karat);
       $idNomens   = $karatNomen->getNomensID($nomen);
       $retArray = $karatNomen->loadPrilohaNomen(key($idNomens));
          //= $karatParam->loadParamForNomen(key($idNomens));
       return $retArray;
   }//end function loadProducer.........................................................................................
   
   
    /**
     * nacte jednu polozku stromu primo z karatu
     * @param int $id - id zaznamu, ktery se ma nacist
     */
    public function loadKaratItem($code){
    	$karatStrom = new A2bModels\karatStrom($this->karatService);
    	return $karatStrom->loadSingleStromItem($code);
    }//.................................................................................................................

  
  

//------------------------------------------------------------------------------------------------end class menuPModel
}//end class karatFactory
//------------------------------------------------------------------------------------------------end class menuPModel