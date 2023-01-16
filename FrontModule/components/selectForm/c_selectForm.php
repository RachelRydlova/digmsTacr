<?php
namespace KataFrontModule;
use Nette, A2bModels;

/**
 * Komponenta starajici se o obsluhu formulare slouziciho pro vyber hodnot
 *
 * @author PC
 */
class c_selectForm extends Nette\Application\UI\Control {

    /**
     * nazev session section pouzivane pro ukladani hodnot formulare
     */
    CONST sessionName = 'selectForm';
    
    /**
     *
     * @var A2bModels\appInfo
     */
    protected $app;

    
    /**
     *
     * @var Nette\Http\Session 
     */
    protected $session;


    public function __construct(A2bModels\appInfo $app, Nette\Http\Session $session) {
        $this->app = $app;
        $this->session = $session->getSection(self::sessionName);
    }



    public function render(){
        $template = $this->template;
        $template->registerHelperLoader('A2bFunc\Helpers::loader');
        // načtu soubor se šablonou formuláře
        $template->setFile(dirname(__FILE__) . '/UserForm.latte');
        $template->render();

    }//end function render

    protected function createComponentKaratTree(){
        return $this->presenter->context->createKaratKataTree();//('karatTree');//createKaratTree;;
    }

    /**
     * formular pro vyber parametru generovai
     * @return \Nette\Application\UI\Form
     */
    protected function createComponentChoiceForm() {
        
        $optionedItem = array('CISLO_NOMENKLATURY'=>'číslo nomenklatury',
                              'NAZEV'             =>'název nomenklatury',
                              'prilohy'           =>'obrázek',
                              'hmotnost'          =>'hmotnost',
                              'poznamka'          =>'poznámka',
                              'barCode'           =>'čárový kód'  
                              );
        
        $form       = new Nette\Application\UI\Form;
        $layoutType = array('base'=>'Základní','short'=>'Stručný');
        
        $form->addGroup();
        $form->addSelect('layoutType','Zvolte typ layotu: ',$layoutType);
        
        $form->addGroup('Tištěné údaje: ');
        $option = $form->addContainer('options');
        
        foreach ($optionedItem as $key=>$caption){
            $option->addCheckbox($key, $caption)->setDefaultValue(TRUE);
        }
        
        

        $form->addGroup('Karat volba kategorii: ');
         // strom kategorií pro Karát
        $form->addButton("treeKarat", "Strom kategorií Karátu")
             ->setAttribute("class", "treeKarat");
        
        $form->addGroup('Obsluha: ');
        $form->addSubmit('generate','Vygenerovat katalog >>')
             ->setAttribute('onClick',"document.getElementById('startFormSpinnerRun').style.display='block'");
        
        //pokud je v session
        if ($this->session->choiceFormArr) $form->setDefaults($this->session->choiceFormArr);
        
        $form->onSuccess[] = callback($this,'proccessChoiceForm');
        
        return $form;

    }//end function ComponentChoiceForm.................................................................................

    /**
     * zpracovani formulare a tisk katalogu
     * @param Nette\Application\UI\Form $form
     * @return boolean
     */
    public function proccessChoiceForm(Nette\Application\UI\Form $form){
        $karat = $this->session->karatMenu;
        if (!$this->session->karatMenu){
            $form->addError('Vyberte větve stromu karatu, z kterých se má generovat katalog');
            return FALSE;
        }
        
        $this->session->choiceFormArr = $form->getValues(TRUE);//pro nastaveni formulare pri navratu
        $this->session->choiceForm    = $form->getValues();
        
        $this->presenter->redirect('generate');
        
    }//end function proccessChoiceForm..................................................................................

}//end class c_selectForm

?>
