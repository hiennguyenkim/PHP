<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\View\Model\ViewModel;
use Application\Core\Session;
use Application\Model\BookModel;
use Application\Model\LoanModel;
use Application\Model\UserModel;

class DashboardController extends BaseController
{
    private BookModel $bookModel;
    private LoanModel $loanModel;
    private UserModel $userModel;

    public function __construct(BookModel $bookModel, LoanModel $loanModel, UserModel $userModel)
    {
        $this->bookModel = $bookModel;
        $this->loanModel = $loanModel;
        $this->userModel = $userModel;
    }

    public function indexAction(): ViewModel
    {
        if ($r = $this->requireLogin()) {
            return $r;
        }

        if (! $this->isAdmin()) {
            $currentUser = $this->currentUser();
            $userId      = (int) ($currentUser['id'] ?? 0);
            $loanSummary = $this->loanModel->getSummary($userId);

            return new ViewModel([
                'title'          => 'Tong quan ban doc',
                'isAdmin'        => false,
                'stats'          => [
                    [
                        'label' => 'Yeu cau cho duyet',
                        'value' => $loanSummary['pending'],
                        'hint' => 'Cac yeu cau dang cho thu thu xac nhan',
                        'tone' => 'primary',
                    ],
                    [
                        'label' => 'Sach dang muon',
                        'value' => $loanSummary['borrowed'],
                        'hint' => 'So dau sach ban dang giu',
                        'tone' => 'blue',
                    ],
                    [
                        'label' => 'Sach qua han',
                        'value' => $loanSummary['overdue'],
                        'hint' => 'Nen tra som de tranh bi nhac han',
                        'tone' => 'red',
                    ],
                    [
                        'label' => 'Da hoan tat',
                        'value' => $loanSummary['returned_total'],
                        'hint' => 'Tong so luot muon da tra',
                        'tone' => 'teal',
                    ],
                ],
                'loanSummary'    => $loanSummary,
                'recentLoans'    => $this->loanModel->getRecentForUser($userId, 6),
                'availableBooks' => $this->bookModel->getAvailableHighlights(6),
                'memberName'     => (string) ($currentUser['full_name'] ?? $currentUser['username'] ?? 'Ban doc'),
            ]);
        }

        $bookSummary = $this->bookModel->getSummary();
        $loanSummary = $this->loanModel->getSummary();
        $userSummary = $this->userModel->getSummary();

        return new ViewModel([
            'title'         => 'Bang dieu khien thu vien',
            'isAdmin'       => true,
            'stats'         => [
                [
                    'label' => 'Dau sach',
                    'value' => $bookSummary['total_books'],
                    'hint' => $bookSummary['available_books'] . ' dau sach con ban sao kha dung',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Ban sao san sang',
                    'value' => $bookSummary['available_copies'],
                    'hint' => 'Tong so sach chua duoc muon trong kho',
                    'tone' => 'teal',
                ],
                [
                    'label' => 'Thanh vien hoat dong',
                    'value' => $userSummary['active_members'],
                    'hint' => $userSummary['admins'] . ' quan tri vien va thu thu',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Luot muon qua han',
                    'value' => $loanSummary['overdue'],
                    'hint' => $loanSummary['borrowed'] . ' luot muon dang dien ra',
                    'tone' => 'red',
                ],
            ],
            'bookSummary'   => $bookSummary,
            'loanSummary'   => $loanSummary,
            'userSummary'   => $userSummary,
            'recentLoans'   => $this->loanModel->getRecent(6),
            'lowStockBooks' => $this->bookModel->getLowStock(5),
            'recentMembers' => $this->userModel->getRecentMembers(5),
        ]);
    }
}
