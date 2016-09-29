<?php

namespace mgcode\uuid\components;

use mgcode\helpers\NumberHelper;
use \Yii;
use yii\base\Component;
use yii\base\InvalidParamException;
use yii\db\Expression;
use yii\db\Query;
use yii\web\Cookie;

/**
 * @link https://github.com/mg-code/yii2-uuid
 * @author Maris Graudins <maris@mg-interactive.lv>
 * @property string $uuid
 */
class UuidComponent extends Component
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
     * @var int Limit of event action name length.
     */
    public $eventActionLength = 25;

    /**
     * @var int Limit of param name length.
     */
    public $paramLength = 25;

    private $_uuid;

    /**
     * Returns unique user ID
     * @return string
     */
    public function getUuid()
    {
        if ($this->_uuid !== null) {
            return $this->_uuid;
        }

        if ($uuid = Yii::$app->request->cookies->getValue($this->cookieName)) {
            $this->_uuid = (string) $uuid;
            return $this->_uuid;
        }

        $uuid = NumberHelper::getGuid();
        $this->setUuid($uuid);
        return $this->_uuid;
    }

    /**
     * Sets unique user ID and saves it in cookie.
     * @param $uuid
     */
    public function setUuid($uuid)
    {
        $params = array_merge([
            'name' => $this->cookieName,
            'value' => $uuid,
            'expire' => strtotime('+2 years'),
        ], $this->cookieParams);

        $cookie = new Cookie($params);
        Yii::$app->response->cookies->add($cookie);
        $this->_uuid = $uuid;
    }

    /**
     * Tracks user event
     * @param string $action
     * @param null|int $target
     * @param null|string $value
     * @throws \yii\db\Exception
     */
    public function trackEvent($action, $target = null, $value = null)
    {
        if (strlen($action) > $this->eventActionLength) {
            throw new InvalidParamException('Action name is too long.');
        }

        Yii::$app->db->createCommand()->insert('uuid_event', [
            'uuid' => $this->getUuid(),
            'action' => $action,
            'target' => $target,
            'value' => $value,
            'datetime' => new Expression('NOW()'),
        ])->execute();
    }

    /**
     * Groups event targets by action and returns count.
     *
     * @param mixed $action
     * @param bool $orderByCount Orders by count. Default: false
     * @return array
     */
    public function groupEventTargets($action, $orderByCount = false)
    {
        $query = (new Query())
            ->select(['target', 'COUNT(*) as count'])
            ->from('uuid_event')
            ->where(['uuid' => $this->getUuid()])
            ->andWhere(['action' => $action])
            ->groupBy(['target']);

        if($orderByCount) {
            $query->orderBy(['count' => SORT_DESC]);
        }

        return $query->all();
    }

    /**
     * Counts user tracked events.
     * False means, that attribute will not be used.
     * Null values can be used.
     * @param mixed $action
     * @param mixed $target
     * @param mixed $value
     * @return int
     */
    public function countEvents($action = false, $target = false, $value = false)
    {
        $query = (new Query())
            ->select('COUNT(*)')
            ->from('uuid_event')
            ->where(['uuid' => $this->getUuid()]);

        if ($action !== false) {
            $query->andWhere(['action' => $action]);
        }

        if ($target !== false) {
            $query->andWhere(['target' => $target]);
        }

        if ($value !== false) {
            $query->andWhere(['value' => $value]);
        }

        return (int) $query->scalar();
    }

    /**
     * Sets parameter for current user
     * @param $param
     * @param $value
     * @throws \yii\db\Exception
     */
    public function setParam($param, $value)
    {
        if (strlen($param) > $this->paramLength) {
            throw new InvalidParamException('Param name is too long.');
        }

        if (!is_scalar($value) && !is_null($value)) {
            $value = serialize($value);
        } else if (is_bool($value)) {
            $value = (int) $value;
        }

        $columns = [
            'uuid' => $this->getUuid(),
            'param' => $param,
            'value' => $value,
            'datetime' => new Expression('NOW()'),
        ];

        if ($this->paramExists($param)) {
            Yii::$app->db->createCommand()->update('uuid_param', $columns, [
                'uuid' => $this->getUuid(),
                'param' => $param,
            ])->execute();
        } else {
            Yii::$app->db->createCommand()->insert('uuid_param', $columns)->execute();
        }
    }

    /**
     * Returns saved parameter value
     * @param string $param
     * @return bool|string
     */
    public function getParam($param)
    {
        $result = (new Query())
            ->select('value')
            ->from('uuid_param')
            ->where([
                'uuid' => $this->getUuid(),
                'param' => $param,
            ])
            ->one();
        return $result ? $result['value'] : null;
    }

    /**
     * Checks whether parameters already set
     * @param string $param
     * @return bool|string
     */
    public function paramExists($param)
    {
        $result = (new Query())
            ->select(new Expression('1'))
            ->from('uuid_param')
            ->where([
                'uuid' => $this->getUuid(),
                'param' => $param,
            ])
            ->scalar();
        return $result;
    }

    /**
     * Remover parameter
     * @param string $param
     * @throws \yii\db\Exception
     */
    public function removeParam($param)
    {
        Yii::$app->db->createCommand()->delete('uuid_param', [
            'uuid' => $this->getUuid(),
            'param' => $param,
        ])->execute();
    }
}