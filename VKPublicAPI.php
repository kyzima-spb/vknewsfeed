<?php
namespace kyzimaspb\vknewsfeed;

/**
 * Базовый класс-обертка для работы с открытыми методами ВКонтакте API.
 *
 * @package kyzimaspb\vknewsfeed
 */
class VKPublicAPI {
    /**
     * Версия API.
     * @var string
     */
    public $version = '5.23';


    /**
     * Выполняет обработку json-ответа сервера.
     *
     * @throws \Exception
     * @param string $resp ответ сервера.
     * @return object возвращает успешный ответ сервера.
     */
    protected function _responseHandler($resp) {
        $resp = json_decode($resp);

        if (isset($resp->error)) {
            throw new \Exception($resp->error->error_msg, $resp->error->error_code);
        }

        return $resp->response;
    }

    /**
     * Возвращает ответ сервера (см. документацию по методам API).
     *
     * @throws \Exception
     * @param string $method название метода из списка функций API,
     * @param array $params параметры соответствующего метода API.
     * @return object возвращает ответ сервера (см. документацию по методам API).
     */
    public function callPublicMethod($method, array $params=array()) {
        return $this->_responseHandler(
            file_get_contents( $this->getApiUrl($method, $params) )
        );
    }

    /**
     * Возвращает URL-адрес для доступа к API.
     *
     * @param string $method название метода из списка функций API,
     * @param array $params параметры соответствующего метода API.
     * @return string возвращает URL-адрес для доступа к API.
     */
    public function getApiUrl($method, array $params=array()) {
        $params['v'] = $this->version;
        return 'https://api.vk.com/method/' . $method . '?' . http_build_query($params);
    }
}