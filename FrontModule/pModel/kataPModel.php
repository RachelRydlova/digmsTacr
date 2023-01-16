<?php

namespace A2bModels;

use A2bModels as A2B;

/**
 * p-model aplikaca kata, vyuzito pri tvorbe menu
 * @author     ondra310
 * @package    vapolDPrice
 * @copyright (c) 02/2013, ondra310
 */
class kataPModel extends A2B\pModelBase {
    /**
     * @var karatModel
     */
    protected $karatModel;

    /**
     * menuProduct model
     * @var menuProduct
     */
    protected $menuProduct;
    
    /**
     *
     * @var connectTecDoc
     */
    protected $tecDocService;
    
    /**
     *  @var $connectKarat
     */
    protected $karatService;
    
    /**
     *
     * @var connectLocal
     */
    protected $connectLocal;
    
    /**
     *
     * @var array
     */
    protected $loadedTempChild = array();
    

    /**
     *
     * @param connectLocal $orm
     * @param connectTecDoc $tecDoc
     * @param connectKarat $karatService 
     */
    public function __construct(A2B\connectLocal $local, A2B\connectKarat $karatService){
    	$this->menuProduct = new A2B\menuProductAdmin($local);
	$this->karatService = $karatService;
	$this->local = $local;
        }

   /**
    * @return A2B\connectKarat
    */    
   public function getKaratService(){
       return $this->karatService;
   }    


  

     /**
     * nacte seznam dostupnych typu menu
     */
    public function loadMenuTypeList(){
    	return $this->local->database->table('menu_type')->fetchPairs('id','nazev');
    }


    /**
     * nacte jednu polozku stromu primo z karatu
     * @param int $id - id zaznamu, ktery se ma nacist
     */
    public function loadKaratItem($code){
    	$karatStrom = new A2B\karatStrom($this->karatService);
    	return $karatStrom->loadSingleStromItem($code);
    }

  

     
    
    //--------------------------------------------------importni cast pro menu ---------------------------------------------
    


   
  

//------------------------------------------------------------------------------------------------end class menuPModel
}//end class menuPModel
//------------------------------------------------------------------------------------------------end class menuPModel