<?php
declare(strict_types=1);

namespace Library\Controller;

use Library\Session\AuthSessionContainer;
use Laminas\Http\Response;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress PossiblyUnusedMethod
 */
class HomeController extends BaseController
{
    public function __construct(AuthSessionContainer $authSessionContainer)
    {
        parent::__construct($authSessionContainer);
    }

    public function indexAction()
    {
        if ($this->currentUser() !== null) {
            return $this->redirect()->toRoute('library/dashboard');
        }

        return $this->redirect()->toRoute('library/auth', ['action' => 'login']);
    }
}
