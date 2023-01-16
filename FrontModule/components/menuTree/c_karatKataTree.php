<?php

namespace KataFrontModule;

use Nette\Application\UI\Control,
    Nette\Application\UI\Form,    
    A2bModels\appInfo;
    

/**
 * Komponenta pro vytvoření stromového menu
 * 
 * @author     Ondra310
 * @package    vapolDPrice
 * @copyright (c) 05/2013, Ondra310
 */
class c_karatKataTree extends Control {

    /**
     * cas, jak dlouho se bude cachovat tecDoce Menu
     */
    CONST cacheKaratTreeTime = ' 60 minutes '; //one half day
    /**
     * název chache
     */
    CONST cacheName = 'karatTree';

    /**
     * @var appInfo
     */
    public $app;

    /**
     * @var \A2bModels\menuProductAdmin
     */
    protected $menuProductAdmin;

    /**
     * @var int - menuID, aktualni id editovaneho zaznamu
     */
    protected $menuID;
    
    /**
     * @var array - pole s nactenou aktualni zpracovavanou kategorii
     */
    protected $currentItem;

    /**
     *
     * @var \Nette\Http\Session
     */
    protected $session;

    /**
     * @var \A2bModels\karatKatSkup
     */
    protected $karatSkup;

    /** ----------------------------------------------------------------------------------------------------------------
     *                                                                                              function __construct
     * 
     * c_MenuTree - konstruktor
     * @param \A2bModels\appInfo $app
     */
    public function __construct(appInfo $app, \A2bModels\karatKatSkup $karatKatSkup, \Nette\Http\Session $session){
        $this->app    = $app;
        $this->menuID = NULL;
        $this->currentItem = NULL;
        $this->session = $session->getSection(c_selectForm::sessionName);
        $this->karatSkup = $karatKatSkup;
    }//end function __construct.........................................................................................

    
    /** ----------------------------------------------------------------------------------------------------------------
     *													 function render
     * 
     * c_MenuTree - vyrendrování komponenty 
     */  
    public function render() {
        // načtu soubor se šablonou
        $template = $this->template;
        // nastavím překlad šablony
        $template->setTranslator($this->app->translator);
        // načtu soubor se šablonou formuláře
        $template->setFile(__DIR__. '/karatKataTree.latte');
        
        //ziskam list stavajicich vybranych kategorii tecdocu abych je mohl zatrhnout v menu
        $loadedMenu =  $this->session->karatMenu;
        
        //vyplnene hodnoty formulare, pokud bytli v process, jsou ve tvaru, array_id=>value, potrebuje transformovat
        $template->tecCheck = is_array($loadedMenu)?array_flip($loadedMenu):array();
        
        $template->tree = $this->loadKaratMenu();
        
        $template->render();
    }//END function render..............................................................................................
    
    
    /** ----------------------------------------------------------------------------------------------------------------
     *                                                                               function createComponentKategorForm
     *
     * tree - formulář pro vložení a editaci stránky, model nette
     * @return \Nette\Application\UI\Form
     */
    protected function createComponentKaratTreeForm() {

        $form = new Form();
        
        // vložím inputy do formuláře
        foreach ($this->loadKaratMenu() as $key=>$box){
            $form->addCheckbox($key);
        }
        
        $form->addSubmit('ulozit_karat');
        // zpracování dat z formuláře
        $form->onSuccess[] = callback($this, 'processKategorForm');
        
        return $form;
    }//END function createComponentKategorForm..........................................................................

    /** ----------------------------------------------------------------------------------------------------------------
     *                                                                                       function processKategorForm
     *
     * tree - zpracování formuláře pro prirazeni kategorie v menu
     * @param  $form - formulář s daty
     */
    public function processKategorForm(Form $form){
        // uložím data z formuláře do DB
        
        $values = $form->getValues(TRUE);
        $selectedValue = array();

        foreach ($values as $key=>$value){
            if ($value){$selectedValue[] = $key;}//endif za value
        }//endforeach
        $this->session->karatMenu = $selectedValue;
        $this->redirect('this');
    }//END function processKategorForm..................................................................................


    //------------------------------------------------------------------------------------------------------------------
    /**
     * vytvori plochou strukturu z menu
     * @param array $array
     * @return array
     */
    protected function getFlatArray($array){
        if (!isSet($retArr)){$retArr = array();}//inicializace, pokud neni instancovana
        if (is_array($array)){//pokud je pole, tak to prochazim a hledam vetve, ktere jiz nemaji potomky
            foreach ($array as $key=>$value){
                if (is_array($value)){
                    $retArr = array_merge($retArr,$this->getFlatArray($value));
                }else{
                    $retArr[] = $key;
                }
            }//endforeach
        }else{
            return array($array);
            }
        return $retArr;
    }//end function.....................................................................................................
    
        
    /** ----------------------------------------------------------------------------------------------------------------
     * 
     * nacte pole, ktere se pouzije pro tvorbu stromoveho menu z tecDocu
     * @return array
     */
    protected function loadKaratMenu(){
        $cache = $this->presenter->getService('a2bCache');
        $retArray = $cache->load(self::cacheName);
        if (!$retArray){
            $retArray = $this->karatSkup->loadSkupiny();
            if ($retArray){
                $cache->save(self::cacheName,$retArray, array(\Nette\Caching\Cache::EXPIRE => self::cacheKaratTreeTime));
            }else{
                $retArray = array();
            }
        }//end za retArray
        //array_unshift($retArray, array(0=>'--'));
        return $retArray;
    }//end fucntion loadTecDocMenu......................................................................................
  
}//end class c_MenuTree.................................................................................................
//......................................................................................................................