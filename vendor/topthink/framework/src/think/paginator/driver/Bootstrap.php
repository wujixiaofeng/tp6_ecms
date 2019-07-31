<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: zhangyajun <448901948@qq.com>
// +----------------------------------------------------------------------

namespace think\paginator\driver;

use think\Paginator;

/**
 * Bootstrap ��ҳ����
 */
class Bootstrap extends Paginator
{

    /**
     * ��һҳ��ť
     * @param string $text
     * @return string
     */
    protected function getPreviousButton(string $text = "&laquo;"): string
    {

        if ($this->currentPage() <= 1) {
            return $this->getDisabledTextWrapper($text);
        }

        $url = $this->url(
            $this->currentPage() - 1
        );

        return $this->getPageLinkWrapper($url, $text);
    }

    /**
     * ��һҳ��ť
     * @param string $text
     * @return string
     */
    protected function getNextButton(string $text = '&raquo;'): string
    {
        if (!$this->hasMore) {
            return $this->getDisabledTextWrapper($text);
        }

        $url = $this->url($this->currentPage() + 1);

        return $this->getPageLinkWrapper($url, $text);
    }

    /**
     * ҳ�밴ť
     * @return string
     */
    protected function getLinks(): string
    {
        if ($this->simple) {
            return '';
        }

        $block = [
            'first'  => null,
            'slider' => null,
            'last'   => null,
        ];

        $side   = 3;
        $window = $side * 2;

        if ($this->lastPage < $window + 6) {
            $block['first'] = $this->getUrlRange(1, $this->lastPage);
        } elseif ($this->currentPage <= $window) {
            $block['first'] = $this->getUrlRange(1, $window + 2);
            $block['last']  = $this->getUrlRange($this->lastPage - 1, $this->lastPage);
        } elseif ($this->currentPage > ($this->lastPage - $window)) {
            $block['first'] = $this->getUrlRange(1, 2);
            $block['last']  = $this->getUrlRange($this->lastPage - ($window + 2), $this->lastPage);
        } else {
            $block['first']  = $this->getUrlRange(1, 2);
            $block['slider'] = $this->getUrlRange($this->currentPage - $side, $this->currentPage + $side);
            $block['last']   = $this->getUrlRange($this->lastPage - 1, $this->lastPage);
        }

        $html = '';

        if (is_array($block['first'])) {
            $html .= $this->getUrlLinks($block['first']);
        }

        if (is_array($block['slider'])) {
            $html .= $this->getDots();
            $html .= $this->getUrlLinks($block['slider']);
        }

        if (is_array($block['last'])) {
            $html .= $this->getDots();
            $html .= $this->getUrlLinks($block['last']);
        }

        return $html;
    }

    /**
     * ��Ⱦ��ҳhtml
     * @return mixed
     */
    public function render()
    {
        if ($this->hasPages()) {
            if ($this->simple) {
                return sprintf(
                    '<ul class="pager">%s %s</ul>',
                    $this->getPreviousButton(),
                    $this->getNextButton()
                );
            } else {
                return sprintf(
                    '<ul class="pagination">%s %s %s</ul>',
                    $this->getPreviousButton(),
                    $this->getLinks(),
                    $this->getNextButton()
                );
            }
        }
    }

    /**
     * ����һ���ɵ���İ�ť
     *
     * @param  string $url
     * @param  string $page
     * @return string
     */
    protected function getAvailablePageWrapper(string $url, string $page): string
    {
        return '<li><a href="' . htmlentities($url) . '">' . $page . '</a></li>';
    }

    /**
     * ����һ�����õİ�ť
     *
     * @param  string $text
     * @return string
     */
    protected function getDisabledTextWrapper(string $text): string
    {
        return '<li class="disabled"><span>' . $text . '</span></li>';
    }

    /**
     * ����һ������İ�ť
     *
     * @param  string $text
     * @return string
     */
    protected function getActivePageWrapper(string $text): string
    {
        return '<li class="active"><span>' . $text . '</span></li>';
    }

    /**
     * ����ʡ�ԺŰ�ť
     *
     * @return string
     */
    protected function getDots(): string
    {
        return $this->getDisabledTextWrapper('...');
    }

    /**
     * ��������ҳ�밴ť.
     *
     * @param  array $urls
     * @return string
     */
    protected function getUrlLinks(array $urls): string
    {
        $html = '';

        foreach ($urls as $page => $url) {
            $html .= $this->getPageLinkWrapper($url, $page);
        }

        return $html;
    }

    /**
     * ������ͨҳ�밴ť
     *
     * @param  string $url
     * @param  string    $page
     * @return string
     */
    protected function getPageLinkWrapper(string $url, string $page): string
    {
        if ($this->currentPage() == $page) {
            return $this->getActivePageWrapper($page);
        }

        return $this->getAvailablePageWrapper($url, $page);
    }
}
