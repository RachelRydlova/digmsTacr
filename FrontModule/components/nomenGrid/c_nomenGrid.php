<?php

namespace XmlModule;

/**
 * Komponenta zobrazujici vysledky vyhledavani nomenklatur
 *
 * @author PC
 */
class c_nomenGrid extends \Nette\Application\UI\Control {

    /**
     * nazev session section pouzivane pro ukladani hodnot formulare
     */
    CONST sessionName = 'nomenGrid';
    
    public function __construct(Nette\ComponentModel\IContainer $parent = NULL, $name = NULL) {
        parent::__construct($parent, $name);
    }
    

    public function render($nomens, $needle = NULL){
        $template = $this->template;
        $template->registerHelperLoader('A2bFunc\Helpers::loader');
        $template->setFile(dirname(__FILE__) . '/c_nomenGrid.latte');
        $template->nomens = $nomens;
        $template->nomenNeedle = $needle;
        $template->render();
    }//end function render..............................................................................................

}//end class c_nomenGrid

?>
