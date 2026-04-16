<?php
declare(strict_types=1);

namespace Library\Controller;

use Library\Form\UserForm;
use Library\Model\Entity\User;
use Library\Model\Table\UserTable;
use Library\Session\AuthSessionContainer;
use Laminas\Form\FormElementManager;
use Laminas\Http\Response;
use Laminas\View\Model\ViewModel;
use RuntimeException;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-suppress PossiblyUnusedMethod
 */
class UserController extends BaseController
{
    private const CREATE_FORM_SERVICE = 'Library\Form\UserCreateForm';
    private const EDIT_FORM_SERVICE   = 'Library\Form\UserEditForm';

    public function __construct(
        AuthSessionContainer $authSessionContainer,
        private UserTable $userTable,
        private FormElementManager $formElementManager
    ) {
        parent::__construct($authSessionContainer);
    }

    public function indexAction()
    {
        if ($response = $this->requireAdmin()) {
            return $response;
        }

        $filters = [
            'search' => trim($this->queryString('search')),
            'role'   => $this->queryString('role'),
        ];

        $currentUser = $this->currentUser() ?? ['id' => 0, 'username' => '', 'full_name' => '', 'role' => ''];

        return new ViewModel([
            'users'     => $this->userTable->fetchAll($filters),
            'filters'   => $filters,
            'summary'   => [
                'total'    => $this->userTable->countAll(),
                'admins'   => $this->userTable->countByRole('admin'),
                'students' => $this->userTable->countByRole('student'),
            ],
            'currentId' => $currentUser['id'],
        ]);
    }

    public function addAction()
    {
        if ($response = $this->requireAdmin()) {
            return $response;
        }

        $form = $this->formElementManager->get(self::CREATE_FORM_SERVICE);
        if (! $form instanceof UserForm) {
            throw new RuntimeException('Không thể khởi tạo biểu mẫu tài khoản mới.');
        }

        if ($this->httpRequest()->isPost()) {
            $form->setData($this->postData());

            if ($form->isValid()) {
                /** @var array{username:string, full_name:string, role:string, password:string} $data */
                $data = $form->getData();

                if ($this->userTable->usernameExists($data['username'])) {
                    $form->get('username')->setMessages(['Tên đăng nhập đã tồn tại.']);
                } else {
                    $user = new User();
                    $user->exchangeArray($data);
                    $this->userTable->saveUser(
                        $user,
                        password_hash($data['password'], PASSWORD_DEFAULT)
                    );

                    $this->flash()->addSuccessMessage('Đã tạo tài khoản mới thành công.');

                    return $this->redirect()->toRoute('library/user');
                }
            }
        }

        $view = new ViewModel([
            'form' => $form,
            'mode' => 'add',
        ]);
        $view->setTemplate('library/user/form');

        return $view;
    }

    public function editAction()
    {
        if ($response = $this->requireAdmin()) {
            return $response;
        }

        $id = $this->routeInt('id');

        try {
            $user = $this->userTable->getUser($id);
        } catch (\RuntimeException $exception) {
            $this->flash()->addErrorMessage($exception->getMessage());

            return $this->redirect()->toRoute('library/user');
        }

        $form = $this->formElementManager->get(self::EDIT_FORM_SERVICE);
        if (! $form instanceof UserForm) {
            throw new RuntimeException('Không thể khởi tạo biểu mẫu cập nhật tài khoản.');
        }

        if ($this->httpRequest()->isPost()) {
            $payload = $this->postData();
            $payload['id'] = $id;
            $form->setData($payload);

            if ($form->isValid()) {
                /** @var array{username:string, full_name:string, role:string, password:string} $data */
                $data = $form->getData();

                if ($this->userTable->usernameExists($data['username'], $id)) {
                    $form->get('username')->setMessages(['Tên đăng nhập đã tồn tại.']);
                } elseif (
                    $user->role === 'admin'
                    && $data['role'] !== 'admin'
                    && $this->userTable->countByRole('admin') <= 1
                ) {
                    $form->get('role')->setMessages(['Phải luôn duy trì ít nhất một quản trị viên trong hệ thống.']);
                } else {
                    $updated = new User();
                    $updated->exchangeArray([
                        'id'        => $id,
                        'username'  => $data['username'],
                        'full_name' => $data['full_name'],
                        'role'      => $data['role'],
                    ]);

                    $passwordHash = $data['password'] !== ''
                        ? password_hash($data['password'], PASSWORD_DEFAULT)
                        : null;

                    $this->userTable->saveUser($updated, $passwordHash);

                    if (($this->currentUser()['id'] ?? 0) === $id) {
                        $session = $this->authSession();
                        $session->user = [
                            'id'        => $updated->id,
                            'username'  => $updated->username,
                            'full_name' => $updated->fullName,
                            'role'      => $updated->role,
                        ];
                    }

                    $this->flash()->addSuccessMessage('Đã cập nhật thông tin tài khoản.');

                    return $this->redirect()->toRoute('library/user');
                }
            }
        } else {
            $form->setData(array_merge(
                $user->getArrayCopy(),
                ['password' => '', 'password_confirm' => '']
            ));
        }

        $view = new ViewModel([
            'form' => $form,
            'mode' => 'edit',
            'user' => $user,
        ]);
        $view->setTemplate('library/user/form');

        return $view;
    }
}
