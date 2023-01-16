<?php

namespace FrontModule;

use App\Model\PersonManager;
use App\Model\User;
use App\Model\UserManager;
use BaseComponents\UserProfilForm;
use Nette\Application\UI\Form,
    A2bFunc;
use Nette\Http\Session;
use Nette\Security\AuthenticationException;
use Nette\Utils\Random;
use Nette\Utils\Strings;


/**
 * Class SignPresenter
 * @package FrontModule
 */
class SignPresenter extends A2bFunc\BasePresenter {


    /** @var Session */
    protected $session;

    /** @var UserManager */
    private $userManager;


    /** @var PersonManager @inject */
    public $personManager;


    /**
     * SignPresenter constructor.
     * @param Session $session
     * @param UserManager $userManager
     */
    public function __construct(Session $session, UserManager $userManager)
    {
        parent::__construct();
        $this->session = $session;
        $this->userManager = $userManager;
    }


    public function startup()
    {
        parent::startup();
    }


    public function actionOut()
    {
        $this->user->logout();
        $this->changeAction('in');
    }


    public function beforeRender()
    {
        parent::beforeRender();
        // cesta k výchozí šabloně, kde se budou plnit základní bloky aplikace
//        $this->template->pageLayout = '../../../templates/page-login.latte';
        // nastavení základní šablony
//        $this->template->layout = '@layout-login.latte';
    }


    /**
     * @return UserProfilForm
     */
    public function createComponentSignUpForm()
    {
        $form = new UserProfilForm($this->userManager, $this->personManager);
        $form->setIsRegistration(true);
        $form->onSuccess[] = function ($email) {
            $this->flashMessage('Registrace byla úspěšná', 'success');
            $this->flashMessage('Na zadaný email bylo zasláno potvrzení');
            $this->redirect('in', ['email' => $email]);
        };
        return $form;
    }


    /**
     * @return Form
     */
    protected function createComponentSignInForm()
    {
        $form = new Form();

        //$form->setTranslator($this->tran);

        $form->addText('username', 'Login:')
            ->setRequired('Prosím zadejte přihlašovací jméno!')
            ->setAttribute('class', 'form-control');
//            ->setAttribute('Placeholder','Jméno');

        $form->addPassword('password', 'Heslo:')
            ->setRequired('Zadejte prosím Vaše heslo!')
            ->setAttribute('class', 'form-control');
//            ->setAttribute('Placeholder','Heslo');

        $form->addCheckbox('remember', 'Zapamatovat přihlášení na tomto počítači?')
            ->setAttribute('class', 'radio');

        $form->addHidden('backlink', $this->presenter->getParameter('backlink'));

        $form->addSubmit('submit', '');

        $form->onSuccess[] = [$this, 'signInFormSubmitted'];
        return $form;
    }


    /**
     * @param $form
     * @return bool
     */
    public function signInFormSubmitted(Form $form)
    {
        try {
            $values = $form->getValues();

            $this->getUser()->login($values->username, $values->password);

            // pokud byla zaškrtnutá volba "pamatovat přihlášení" nastaví uložení session na 14 dní
            $this->getUser()->setExpiration('+ 24 hours', false);
            if ($values->remember) {
                $this->getUser()->setExpiration('+ 14 days', false);
            }

            // Last login update
            /** @var User $user */
            $user = $this->userManager->getUserById($this->getUser()->getId());

            // Save last login date for check new activity
            // $this->app->saveValue(User::LASTLOGINSESSDATE, $user->lastLogin);

            $user->lastLogin = new \DateTime();
            $this->userManager->persistUserEntity($user);

            if (isset($values->backlink)) {
                $this->presenter->restoreRequest($values->backlink);
            }
            $this->redirect(':Ev:Dashboard:manager');
        } catch (AuthenticationException $e) {
            $form->addError($e->getMessage());
            return false;
        }
        return true;
    }


    /**
     * @return Form
     */
    public function createComponentRecoverPasswordForm()
    {
        $form = new Form();
        $form->addEmail('email', 'Prosím zadejte email')
            ->setRequired('Prosím zadejte email')
            ->setAttribute('class', 'form-control')
            ->setAttribute('Placeholder','Zde zadejte email');

        $form->addSubmit('submit', 'Odeslat');
        $form->onSuccess[] = [$this, 'recoveryPassword'];

        return  $form;
    }
    

    /**
     * @param Form $form
     */
    public function recoveryPassword(Form $form)
    {
        $email = $form->getValues()->email;
        $user = $this->userManager->getUserByCond(['email' => $email]);
        if ((bool) $user === false) {
            $this->flashMessage('Tento email neni v seznamu uživatelů registrován', 'warning');
            $this->redirect('in');
        } else {
            // Vygeneruj nove heslo
            $newPassword = Random::generate();
            // Posli na email
            $values = new \stdClass();
            $values->email = $email;
            $values->password = $newPassword;
            if ($this->userManager->sendRecoveredPassword($values)) {
                // Uloz zaheshovane heslo
                $user->password = \BaseAuthenticator::calculateHash($newPassword);
                $this->userManager->persistUserEntity($user);
                $this->flashMessage('Na email jsem Vám zaslali obnovený přístup');
            } else {
                $this->flashMessage('Omlouvám se, obnova se nepodařila, zkuste to prosím znovu nebo nás kontaktujte', 'error');
            }
            $this->redirect('in');
        }
    }


    public function renderRecoverPassword()
    {
        $this->template->presenterTitle = 'Zapomenuté heslo';
    }


    /**
     * @param null|string $email
     */
    public function renderIn($email = NULL)
    {
        if ($email) {
            $this['signInForm']['username']->setDefaultValue($email);
            $this['signInForm']['password']->setAttribute('autofocus');
        }
        $this->template->presenterTitle = 'LOGIN';
    }


    public function renderUp()
    {
        $this->template->presenterTitle = 'REGISTRACE';
    }

}