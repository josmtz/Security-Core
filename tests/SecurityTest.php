<?php

declare(strict_types=1);

/*
 * This file is part of Security Core.
 *
 * (c) Graham Campbell <hello@gjcampbell.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GrahamCampbell\Tests\SecurityCore;

use GrahamCampbell\SecurityCore\Security;
use PHPUnit\Framework\TestCase;
use voku\helper\AntiXSS;

/**
 * This is the security test class.
 *
 * @author Graham Campbell <hello@gjcampbell.co.uk>
 * @author Lars Moelleken <lars@moelleken.org>
 */
class SecurityTest extends TestCase
{
    public static function snippetProvider(): array
    {
        $cases = [
            [
                'Hello, try to <script>alert(\'Hack\');</script> this site',
                'Hello, try to  this site',
            ],
            [
                '<a href="&#38&#35&#49&#48&#54&#38&#35&#57&#55&#38&#35&#49&#49&#56&#38&#35&#57&#55&#38&#35&#49&#49&#53&#38&#35&#57&#57&#38&#35&#49&#49&#52&#38&#35&#49&#48&#53&#38&#35&#49&#49&#50&#38&#35&#49&#49&#54&#38&#35&#53&#56&#38&#35&#57&#57&#38&#35&#49&#49&#49&#38&#35&#49&#49&#48&#38&#35&#49&#48&#50&#38&#35&#49&#48&#53&#38&#35&#49&#49&#52&#38&#35&#49&#48&#57&#38&#35&#52&#48&#38&#35&#52&#57&#38&#35&#52&#49">Clickhere</a>',
                '<a href="(1)">Clickhere</a>',
            ],
            [
                '&foo should not include a semicolon',
                '&foo should not include a semicolon',
            ],
            [
                './<!--foo-->',
                './&lt;!--foo--&gt;',
            ],
            [
                '<div style="color:rgb(\'\'&#0;x:expression(alert(1))"></div>',
                '<div ></div>',
            ],
            [
                '<img/src=%00 id=confirm(1) onerror=eval(id)',
                '&lt;img/',
            ],
            [
                '<div id=confirm(1) onmouseover=eval(id)>X</div>',
                '<div id=confirm&#40;1&#41; >X</div>',
            ],
            [
                '<span/onmouseover=confirm(1)>X</span>',
                '<span/>X</span>',
            ],
            [
                '<svg/contentScriptType=text/vbs><script>Execute(MsgBox(chr(88)&chr(83)&chr(83)))',
                '&lt;svg/contentScriptType=text/vbs&gt;',
            ],
            [
                '<iframe/src="javascript:a=[alert&lpar;1&rpar;,confirm&#40;2&#41;,prompt%283%29];eval(a[0]);">',
                '&lt;iframe/src="(1),confirm&#40;2&#41;,prompt&#40;3&#41;];eval&#40;a[0]&#41;;"&gt;',
            ],
            [
                '<div/style=content:url(data:image/svg+xml);visibility:visible onmouseover=alert(1)>x</div>',
                '<div/ >x</div>',
            ],
            [
                '<script>Object.defineProperties(window,{w:{value:{f:function(){return 1}}}});confirm(w.f())</script>',
                '',
            ],
            [
                '<keygen/onfocus=prompt(1);>',
                '&lt;keygen/&gt;',
            ],
            [
                '<img/src=`%00` id=confirm(1) onerror=eval(id)',
                '&lt;img/',
            ],
            [
                '<img/src=`%00` onerror=this.onerror=confirm(1)',
                '&lt;img/',
            ],
            [
                '<iframe/src="data:text/html,<iframe%09onload=confirm(1);>">',
                "&lt;iframe/src=\"data:text/html,<iframe\t>\">",
            ],
            [
                '<math><a/xlink:href=javascript:prompt(1)>X',
                '&lt;math&gt;<a/>X',
            ],
            [
                '<input/type="image"/value=""`<span/onmouseover=\'confirm(1)\'>X`</span>',
                '&lt;input/type="image"/value=""`<span/>X`</span>',
            ],
            [
                '<form/action=javascript&#x0003A;eval(setTimeout(confirm(1)))><input/type=submit>',
                '&lt;form/action=(setTimeout&#40;confirm&#40;1&#41;&#41;)&gt;&lt;input/type=submit&gt;',
            ],
            [
                '<body/onload=this.onload=document.body.innerHTML=alert&lpar;1&rpar;>',
                '&lt;body/&gt;',
            ],
            [
                '<iframe/onload=\'javascript&#58;void&#40;1&#41;&quest;void&#40;1&#41;&#58;confirm&#40;1&#41;\'>',
                '&lt;iframe/&gt;',
            ],
            [
                '<object/type="text/x-scriptlet"/data="data:X,&#60script&#62setInterval&lpar;\'prompt(1)\',10&rpar;&#60/script&#62"></object>',
                '&lt;object/type="text/x-scriptlet"/data="data:X,"&gt;&lt;/object&gt;',
            ],
            [
                '<i<f<r<a<m<e><iframe/onload=confirm(1);></i>f>r>a>m>e>',
                '<i&lt;f&lt;r&lt;a&lt;m&lt;e>&lt;iframe/&gt;</i>f>r>a>m>e>',
            ],
            [
                'http://www.<script abc>setTimeout(\'confirm(1)\',1)</script .com>',
                'http://www.setTimeout&#40;\'confirm&#40;1&#41;\',1&#41;.com>',
            ],
            [
                '<style/onload    =    !-alert&#x28;1&#x29;>',
                '&lt;style/&gt;',
            ],
            [
                '<svg id=a /><script language=vbs for=a event=onload>alert 1</script>',
                '&lt;svg id=a /&gt;alert 1',
            ],
            [
                '<object/data="data&colon;X&comma;&lt;script&gt;alert&#40;1&#41;%3c&sol;script%3e">',
                '&lt;object/data="data:X,"&gt;',
            ],
            [
                '<form/action=javascript&#x3A;void(1)&quest;void(1)&colon;alert(1)><input/type=\'submit\'>',
                '&lt;form/action=(1)?void(1):alert&#40;1&#41;&gt;&lt;input/type=\'submit\'&gt;',
            ],
            [
                '<iframe/srcdoc=\'&lt;iframe&sol;onload&equals;confirm(&sol;&iexcl;&hearts;&xcup;&sol;)&gt;\'>',
                '&lt;iframe/srcdoc=\'&lt;iframe/&gt;\'>',
            ],
            [
                '<meta/http-equiv="refresh"/content="0;url=javascript&Tab;:&Tab;void(alert(0))?0:0,0,prompt(0)">',
                '&lt;meta/http-equiv="refresh"/content="0;url=(alert&#40;0&#41;)?0:0,0,prompt&#40;0&#41;"&gt;',
            ],
            [
                '<script src="h&Tab;t&Tab;t&Tab;p&Tab;s&colon;/&Tab;/&Tab;http://dl.dropbox.com/u/13018058/js.js"></script>',
                '',
            ],
            [
                '<style/onload=\'javascript&colon;void(0)?void(0)&colon;confirm(1)\'>',
                '&lt;style/&gt;',
            ],
            [
                '<svg><style>&#x7B;-o-link-source&#x3A;\'<style/onload=confirm(1)>\'&#x7D;',
                '&lt;svg&gt;&lt;style&gt;{-o-link-source:\'&lt;style/&gt;\'}',
            ],
            [
                '<math><solve i.e., x=2+2*2-2/2=? href="data:text/html,<script>prompt(1)</script>">X',
                '&lt;math&gt;<solve i.e., x=2+2*2-2/2=? href="data:text/html,">X',
            ],
            [
                '<iframe/src="j&Tab;AVASCRIP&NewLine;t:\u0061ler\u0074&#x28;1&#x29;">',
                '&lt;iframe/src="(1)"&gt;',
            ],
            [
                '<iframe/src="javascript:void(alert(1))?alert(1):confirm(1),prompt(1)">',
                '&lt;iframe/src="(alert&#40;1&#41;)?alert&#40;1&#41;:confirm&#40;1&#41;,prompt&#40;1&#41;"&gt;',
            ],
            [
                '<embed/src=javascript&colon;\u0061&#x6C;&#101%72t&#x28;1&#x29;>',
                '&lt;embed/src=(1)&gt;',
            ],
            [
                '<img/src=\'http://i.imgur.com/P8mL8.jpg \' onmouseover={confirm(1)}f()>',
                '<img/src=\'http://i.imgur.com/P8mL8.jpg \'>',
            ],
            [
                '<style/&Tab;/onload=;&Tab;this&Tab;.&Tab;onload=confirm(1)>',
                '&lt;style/	/	this	.	&gt;',
            ],
            [
                '<embed/src=//goo.gl/nlX0P>',
                '&lt;embed/src=//goo.gl/nlX0P&gt;',
            ],
            [
                '<form><button formaction=javascript:alert(1)>CLICKME',
                '&lt;form&gt;&lt;button &gt;CLICKME',
            ],
            [
                '<script>x=\'con\';s=\'firm\';S=\'(1)\';setTimeout(x+s+S,0);</script>',
                '',
            ],
            [
                '<img/id="confirm&lpar;1&#x29;"/alt="/"src="/"onerror=eval(id&#x29;>',
                '<img/id="confirm&#40;1&#41;"alt="/"src="/">',
            ],
            [
                '<iframe/src="data&colon;text&sol;html,<s&Tab;cr&Tab;ip&Tab;t>confirm(1)</script>">',
                '&lt;iframe/src="data:text/html,"&gt;',
            ],
            [
                '<foo fscommand=case-insensitive><foo seekSegmentTime=whatever>',
                '<foo ><foo >',
            ],
            [
                '<foo onAttribute="bar">',
                '<foo >',
            ],
            [
                '<foo onAttributeWithSpaces = bar>',
                '<foo >',

            ],
            [
                '<foo prefixOnAttribute="bar">%0b'."\0",
                '<foo prefixOnAttribute="bar">',
            ],
            [
                "\n><!-\n<b\n<c d=\"'e><iframe onload=alert(1) src=x>\n<a HREF=\"\">\n",
                "\n>&lt;!-\n&lt;b\n&lt;c d=\"'e&gt;&lt;iframe  src=x&gt;\n&lt;a \"\"&gt;\n",
            ],
            [
                '<meta charset="x-imap4-modified-utf7">&<script&S1&TS&1>alert&A7&(1)&R&UA;&&<&A9&11/script&X&>',
                '&lt;meta charset="x-imap4-modified-utf7"&gt;&alert&A7&(1)&R&UA;&&&lt;&A9&11/script&X&&gt;',
            ],
            [
                '<!--\\x3E<img src=xxx:x onerror=javascript:alert(1)> -->%0b'."\0",
                '&lt;!--\x3E<img > --&gt;',
            ],
            [
                '--><!-- --\x3E> <img src=xxx:x onerror=javascript:alert(1)> -->',
                '-->&lt;!-- --\x3E> <img > --&gt;',
            ],
            [
                '<svg/onload=alert(1)',
                '&lt;svg/',
            ],
        ];

        return $cases;
    }

    /**
     * @dataProvider snippetProvider
     */
    public function testCleanString(string $input, string $output): void
    {
        $return = Security::create()->clean($input);

        self::assertSame($output, $return);
    }

    /**
     * @dataProvider snippetProvider
     */
    public function testCleanStringWithExplictEvilList(string $input, string $output): void
    {
        $evil = [
            '(?<!\w)on\w*',
            'style',
            'xmlns',
            'formaction',
            'form',
            'xlink:href',
            'FSCommand',
            'seekSegmentTime',
        ];

        $return = Security::create($evil)->clean($input);

        self::assertSame($output, $return);
    }

    public function testCleanArray(): void
    {
        $security = Security::create(['test'], '[removed]');

        $return = $security->clean(['<script>', '<li test="alert();">', '123', ['abc']]);

        self::assertSame(['[removed]', '<li [removed]>', '123', ['abc']], $return);
    }

    public function testCleanDeeplyNestedArray(): void
    {
        $security = Security::create(['test'], '[removed]');

        $return = $security->clean(['<script>', '<li test="alert();">', '123', ['abc', [[['<li test="alert();">']]]]]);

        self::assertSame(['[removed]', '<li [removed]>', '123', ['abc', [[['<li [removed]>']]]]], $return);
    }

    public function testCleanWithCustomAntiXss(): void
    {
        $replacer = '[removed]';

        $antiXss = new AntiXSS();
        $antiXss->removeEvilAttributes(['style']); // allow style-attributes
        $antiXss->setReplacement($replacer);
        $antiXss->addNeverAllowedRegex(['test.*' => $replacer]);

        $security = new Security($antiXss);

        $return = $security->clean(['<script>', 'testFoo', '123', ['<abc style="border: 0;">']]);

        self::assertSame(['[removed]', '[removed]', '123', ['<abc style="border: 0;">']], $return);
    }
}
