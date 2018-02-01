<?php
/**
 * Created by PhpStorm.
 * User: yasushi_sakita
 * Date: 15/12/26
 * Time: 20:50
 */

namespace AppBundle\Controller;

use AppBundle\Dto\UserDto;
use Sagojo\BackendBundle\Dto\Notification;
use Sagojo\BackendBundle\Dto\SagojoStaffDto;
use Sagojo\BackendBundle\Entity\SagojoStaff;
use Sagojo\BackendBundle\Entity\User;
use Sagojo\BackendBundle\Entity\Chat;
use Sagojo\BackendBundle\Entity\ChatRead;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
class BaseController extends Controller
{

    private function getSession() {
        return $this->get('session');
    }

    protected function getAttribute($name) {
        return $this->getSession()->get($name);
    }

    protected function hasAttribute($name) {
        return $this->getSession()->has($name);
    }

    protected function setAttribute($name, $value) {
        $this->getSession()->set($name, $value);
    }

    // セッションを破棄するメソッド
    protected function deleteAttribute($key) {
        $this->getSession()->remove($key);
    }

    protected function clearSession() {
        $this->getSession()->clear();
    }



    public function setReferer($requestUrl) {
        $this->setAttribute('referer', $requestUrl);
    }

    public function getReferer() {
        return $this->getAttribute('referer');
    }

    public function deleteReferer() {
        $this->deleteAttribute('referer');
    }

    public function hasReferer() {
        return $this->hasAttribute('referer');
    }

    public function _addFlash($type, $message) {
        $this->addFlash($type, $message);
    }

}