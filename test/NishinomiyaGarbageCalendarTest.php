<?php

use PHPUnit\Framework\TestCase;
use PHPHtmlParser\Dom;
use Gomicale\NishinomiyaGarbageCalendar;

class NishinomiyaGarbageCalendarTest extends TestCase
{

    //  /**
    //  * @var Dom
    //  */
    // private $dom;

    // public function setUp(): void
    // {
    //     $dom = new Dom();
    //     $dom->loadFromFile('tests/data/files/nishinomiya-gabage-calendar.html');
    //     $this->dom = $dom;
    // }

    public function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @test
     */
    public function testCreateUrl()
    {
        $result = NishinomiyaGarbageCalendar::createUrl(2021, 7, 123);
        $this->assertSame('https://www.nishi.or.jp/homepage/gomicalendar/calendar.html?date=2021-7&id=123', $result);
    }

    /**
     * @test
     */
    public function testGetCalenarTable()
    {
        $tableHtml = '<table class="calendar"><tbody><tr><th class="sunday"><span>日</span></th></tr></tbody></table>';
        $html = '<html dir="ltr" lang="ja"><head></head><body id="top">' . $tableHtml . '</body></html>';
        $dom = new Dom();
        $dom->loadStr($html);
        $nishinomiyaGabageCalenar = new NishinomiyaGarbageCalendar($dom);
        $this->assertSame($tableHtml, (string) $nishinomiyaGabageCalenar->getCalendarTable());
    }
    /**
     * 対象のテーブルがない場合
     * @test
     */
    public function testGetCalenarTableEmpty()
    {
        $tableHtml = '<table class="dummy"><tbody><tr><th class="sunday"><span>日</span></th></tr></tbody></table>';
        $html = '<html dir="ltr" lang="ja"><head></head><body id="top">' . $tableHtml . '</body></html>';
        $dom = new Dom();
        $dom->loadStr($html);
        $nishinomiyaGabageCalenar = new NishinomiyaGarbageCalendar($dom);
        $this->assertEmpty($nishinomiyaGabageCalenar->getCalendarTable());
    }

    /**
     * @test
     */
    public function testGetGarbageInfoArray()
    {

        $gabageInfoText = 'もやさないごみ';
        $gabageInfoHtml = '<table class="calendar"><tbody><tr><th class="sunday"><span>日</span></th></tr>'
            . '<tr><td><div class="cell"><p class="date">1</p><p class="moyasanai item">'
            . $gabageInfoText . '</p></div></td></tr>'
            . '</tbody></table>';
        $html = '<html dir="ltr" lang="ja"><head></head><body id="top">' . $gabageInfoHtml . '</body></html>';
        $dom = new Dom();
        $dom->loadStr($html);
        $nishinomiyaGabageCalenar = new NishinomiyaGarbageCalendar($dom);
        $gabageInfoArray = $nishinomiyaGabageCalenar->getGarbageInfoArray(1);
        $this->assertCount(1, $gabageInfoArray);
        $this->assertSame(array($gabageInfoText), $gabageInfoArray);
    }

    /**
     * @test
     */
    public function testGetGarbageInfoArray_ゴミ情報が複数あるケース()
    {

        $gabageInfoText1 = 'その他プラ';
        $gabageInfoText2 = '資源B(雑誌等)';
        $gabageInfoHtml = '<table class="calendar"><tbody><tr><th class="sunday"><span>日</span></th></tr>'
            . '<tr><td><div class="cell"><p class="date">1</p><p class="sonota item">'
            .$gabageInfoText1.'</p><p class="shigenb item">資源B<br>(雑誌等)</p></div></td></tr>'
            . '</tbody></table>';
        $html = '<html dir="ltr" lang="ja"><head></head><body id="top">' . $gabageInfoHtml . '</body></html>';
        $dom = new Dom();
        $dom->loadStr($html);
        $nishinomiyaGabageCalenar = new NishinomiyaGarbageCalendar($dom);
        $gabageInfoArray = $nishinomiyaGabageCalenar->getGarbageInfoArray(1);
        $this->assertCount(2, $gabageInfoArray);
        $this->assertSame(array($gabageInfoText1, $gabageInfoText2), $gabageInfoArray);
    }

    /**
     * @test
     */
    public function testGetGarbageInfoArray_ごみがない日()
    {

        $gabageInfoHtml = '<table class="calendar"><tbody><tr><th class="sunday"><span>日</span></th></tr>'
            . '<tr><td><div class="cell"><p class="date">1</p></div></td></tr>'
            . '</tbody></table>';
        $html = '<html dir="ltr" lang="ja"><head></head><body id="top">' . $gabageInfoHtml . '</body></html>';
        $dom = new Dom();
        $dom->loadStr($html);
        $nishinomiyaGabageCalenar = new NishinomiyaGarbageCalendar($dom);
        $gabageInfoArray = $nishinomiyaGabageCalenar->getGarbageInfoArray(1);
        $this->assertCount(1, $gabageInfoArray);
        $this->assertSame(array(NishinomiyaGarbageCalendar::NO_GARBAGE_INFO), $gabageInfoArray);
    }

        /**
     * @test
     */
    public function testGetGarbageInfoArray_ごみ情報が取れない場合()
    {

        $gabageInfoHtml = '<table class="dummy"><tbody><tr><th class="sunday"><span>日</span></th></tr>'
            . '<tr><td><div class="cell"><p class="date">1</p></div></td></tr>'
            . '</tbody></table>';
        $html = '<html dir="ltr" lang="ja"><head></head><body id="top">' . $gabageInfoHtml . '</body></html>';
        $dom = new Dom();
        $dom->loadStr($html);
        $nishinomiyaGabageCalenar = new NishinomiyaGarbageCalendar($dom);
        $gabageInfoArray = $nishinomiyaGabageCalenar->getGarbageInfoArray(1);
        $this->assertCount(1, $gabageInfoArray);
        $this->assertSame(array(NishinomiyaGarbageCalendar::EMPTY_GARBAGE_INFO), $gabageInfoArray);
    }


    /**
     * @test
     */
    public function testIsTargetDate()
    {
        $this->assertSame(true, NishinomiyaGarbageCalendar::isTargetDate('1', 1));
        $this->assertSame(true, NishinomiyaGarbageCalendar::isTargetDate('01', 1));
        $this->assertSame(false, NishinomiyaGarbageCalendar::isTargetDate('', 1));
        $this->assertSame(false, NishinomiyaGarbageCalendar::isTargetDate('1a', 1));
        $this->assertSame(false, NishinomiyaGarbageCalendar::isTargetDate('a', 1));
        $this->assertSame(false, NishinomiyaGarbageCalendar::isTargetDate('あ', 1));
    }
}
