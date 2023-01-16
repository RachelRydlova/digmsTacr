<?php

namespace FrontModule;

use A2bModels\excelPmodel;
use A2bModels\weatherPmodel;
use Nette\Application\UI\Form,
    Nette;


/**
 * Class DefaultPresenter
 * @package FrontModule
 */
class DefaultPresenter extends FrontBasePresenter {
    



    public function __construct()
    {
        parent::__construct();

    }


    public function actionDefault()
    {
        $this->setBreadCrumb('Úvodní informace');
    }




}