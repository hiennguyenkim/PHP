<?php
declare(strict_types=1);

namespace Library\Controller;

use Library\Form\LoginForm;
use Library\Model\Table\UserTable;
use Library\Session\AuthSessionContainer;
use Laminas\Form\FormElementManager;
use Laminas\Http\Response;
use Laminas\Session\SessionManager;
use Laminas\View\Model\ViewModel;
use RuntimeException;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress PossiblyUnusedMethod
 */
class AuthController extends BaseController
{
    public function __construct(
        AuthSessionContainer $authSessionContainer,
        private UserTable $userTable,
        private FormElementManager $formElementManager,
        private SessionManager $sessionManager
    ) {
        parent::__construct($authSessionContainer);
    }

    public function loginAction()
    {
        if ($this->currentUser() !== null) {
            return $this->redirect()->toRoute('library/dashboard');
        }

        $form = $this->formElementManager->get(LoginForm::class);
        if (! $form instanceof LoginForm) {
            throw new RuntimeException('Không thể khởi tạo biểu mẫu đăng nhập.');
        }

        $request = $this->httpRequest();

        if ($request->isPost()) {
            $form->setData($this->postData());
            if ($form->isValid()) {
                /** @var array{username:string, password:string} $data */
                $data = $form->getData();
                $user = $this->userTable->getByUsername($data['username']);

                if ($user && password_verify($data['password'], $user->password)) {
                    $this->authSession()->user = [
                        'id'        => $user->id,
                        'username'  => $user->username,
                        'full_name' => $user->fullName,
                        'role'      => $user->role,
                    ];
                    $this->flash()->addSuccessMessage('Chào mừng ' . $user->fullName . '!');
                    return $this->redirect()->toRoute('library/dashboard');
                }

                $this->flash()->addErrorMessage('Tên đăng nhập hoặc mật khẩu không đúng.');
            }
        }

        return new ViewModel(['form' => $form]);
    }

    public function logoutAction()
    {
        $this->sessionManager->destroy();
        $this->flash()->addInfoMessage('Bạn đã đăng xuất.');
        return $this->redirect()->toRoute('library/auth', ['action' => 'login']);
    }
}
