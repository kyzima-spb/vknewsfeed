<?php
namespace kyzimaspb\vknewsfeed;


/**
 * Новостная лента социальной сети ВКонтакте, выполненная в стиле Twitter.
 *
 * Поиск записей осуществляется по указанному хеш-тегу.
 * В результаты поиска попадают только записи от пользователей, указанных в VKNewsFeed::owners.
 * Идентификатор сообщества должен быть указан со знаком минус!
 *
 * @package kyzimaspb\vknewsfeed
 */
class VKNewsFeed extends VKPublicAPI {
    /**
     * Хеш-тег, используемый для поиска записей.
     * @var string
     */
    public $hashTag;
    /**
     * Список идентификаторов пользователей и групп,
     * которым разрешено попадать в результаты поиска.
     * @var array
     */
    public $owners = array();


    /**
     * @param string $hashTag хеш-тег, используемый для поиска записей.
     * @param array $owners список идентификаторов пользователей и групп,
     * которым разрешено попадать в результаты поиска.
     */
    public function __construct($hashTag, array $owners=array()) {
        $this->hashTag = $hashTag;
        $this->owners = $owners;
    }

    /**
     * Возвращает краткую информацию об авторе записи.
     *
     * @param mixed $id уникальный идентификатор автора.
     * @param object $resp ответ сервера VK.
     * @return object возвращает краткую информацию об авторе записи.
     */
    protected function _getInfoById($id, $resp) {
        $isGroup = $id < 0;
        $items = $isGroup ? $resp->groups : $resp->profiles;
        $id = abs($id);

        foreach ($items as $item) {
            if ($item->id == $id) {
                return (object) array(
                    'from_id' => $item->id,
                    'name' => $isGroup ? $item->name : $item->first_name.' '.$item->last_name,
                    'avatar' => $item->photo_50,
                    'url' => 'https://vk.com/' . ($isGroup ? 'club' : 'id') . $id,
                );
            }
        }

        return new \StdClass();
    }

    /**
     * Вспомогательный метод-фабрика.
     *
     * @param string $hashTag хеш-тег, используемый для поиска записей.
     * @param array $owners список идентификаторов пользователей и групп,
     * которым разрешено попадать в результаты поиска.
     * @return VKNewsFeed
     */
    static public function getInstance($hashTag, array $owners=array()) {
        return new self($hashTag, $owners);
    }

    /**
     * Возвращает новостную ленту.
     * @return array возвращает новостную ленту.
     */
    public function getFeed() {
        $resp = $this->callPublicMethod('newsfeed.search', array(
            'q' => $this->hashTag,
            'extended' => 1,
        ));

        $feed = array();

        foreach ($resp->items as $news) {
            if (!in_array($news->from_id, $this->owners)) {
                continue;
            }
            $info = $this->_getInfoById($news->from_id, $resp);
            $info->date = $news->date;
            $info->text = $news->text;
            array_push($feed, $info);
        }

        return $feed;
    }

    /**
     * Выполняет рендеринг новостной ленты.
     */
    public function renderFeed() {
        $resp = $this->callPublicMethod('newsfeed.search', array(
            'q' => $this->hashTag,
            'extended' => 1,
        ));

        echo '<ul class="vk-news-feed">';

        foreach ($resp->items as $news) {
            if (!in_array($news->from_id, $this->owners)) {
                continue;
            }

            $info = $this->_getInfoById($news->from_id, $resp);
            $news->text = str_replace($this->hashTag, '', $news->text);

            echo '<li><div class="item">
                    <div class="item-header">
                        <a href="'.$info->url.'" target="_blank">
                            <img class="avatar" src="'.$info->avatar.'" alt="'.$info->name.'" />
                            <strong class="fullname">'.$info->name.'</strong>
                        </a>
                        <span class="time">'.date('Y-m-d H:i:s', $news->date).'</span>
                    </div>
                    <p>'.$news->text.'</p>
                </div></li>';
        }

        echo '</ul>';
    }
}