<?php

namespace mgcode\uuid\components;

use mgcode\helpers\NumberHelper;
use \Yii;
use yii\base\Component;
use yii\base\InvalidParamException;
use yii\db\Expression;
use yii\web\Cookie;

/**
 * @link https://github.com/mg-code/yii2-uid-track
 * @author Maris Graudins <maris@mg-interactive.lv>
 * @property string $uuid
 */
class TrackComponent extends Component
{
    /**
     * @var string
     */
    public $cookieName = '__muid';

    /**
     * @var array parameter-value pairs to override default cookie parameters.
     */
    public $cookieParams = [];

    /**
     * @var int Limit of action name length.
     */
    public $actionNameLength = 25;

    private $_uuid;

    /**
     * Returns unique user ID
     * @return string
     */
    public function getUuid()
    {
        if($this->_uuid !== null) {
            return $this->_uuid;
        }

        if($uid = Yii::$app->request->cookies->getValue($this->cookieName)) {
            $this->_uuid = (string) $uid;
            return $this->_uuid;
        }

        $uid = NumberHelper::getGuid();
        $this->setUuid($uid);
        return $this->_uuid;
    }

    /**
     * Sets unique user ID and saves it in cookie.
     * @param $uuid
     */
    public function setUuid($uuid)
    {
        $params = array_merge($this->cookieParams, [
            'name' => $this->cookieName,
            'value' => $uuid,
        ]);
        $cookie = new Cookie($params);
        Yii::$app->response->cookies->add($cookie);
        $this->_uuid = $uuid;
    }

    /**
     * Tracks user action
     * @param string $action
     * @param null|int $target
     * @param null|string $value
     * @throws \yii\db\Exception
     */
    public function track($action, $target = null, $value = null)
    {
        if(strlen($action) > $this->actionNameLength) {
            throw new InvalidParamException('Action name is too long.');
        }

        Yii::$app->db->createCommand()->insert('uuid_event', [
            'uuid' => $this->getUuid(),
            'action' => $action,
            'target' => $target,
            'value' => $value,
            'created' => new Expression('NOW()'),
        ])->execute();
    }
}