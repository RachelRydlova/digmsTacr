<?php

namespace XmlModule;

/**
 * Komponenta zobrazujici vysledky vyhledavani nomenklatur
 *
 * @author PC
 */
class c_partnerGrid extends \Nette\Application\UI\Control {

    /**
     * nazev session section pouzivane pro ukladani hodnot formulare
     */
    CONST sessionName = 'nomenGrid';
    
    
    public function __construct(Nette\ComponentModel\IContainer $parent = NULL, $name = NULL) {
        parent::__construct($parent, $name);
    }//end function __construct.........................................................................................
    

    public function render($partners){
        $template = $this->template;
        $template->registerHelperLoader('A2bFunc\Helpers::loader');
        $template->setFile(dirname(__FILE__) . '/c_partnerGrid.latte');
        $template->partners = $partners;
        $template->render();
    }//end function render..............................................................................................

}//end class c_nomenGrid................................................................................................

?>
