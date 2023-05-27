<?php

namespace zhanshop\helper;

use zhanshop\App;

/**
1、. 匹配任意除换行符“\n”外的字符；
2、*表示匹配前一个字符0次或无限次；
3、?表示前边字符的0次或1次重复 (有或者没有)
4、+或*后跟？表示非贪婪匹配，即尽可能少的匹配，如*？重复任意次，但尽可能少重复；
5、 .*? 表示匹配任意数量的重复，但是在能使整个匹配成功的前提下使用最少的重复。
如：a.*?b匹配最短的，以a开始，以b结束的字符串。如果把它应用于aabab的话，它会匹配aab和ab。
 */


/**
 * i ：(PCRE_CASELESS)
　　如果设定此修正符，模式中的字符将同时匹配大小写字母。【不区分大小写】
m：（PCRE_MULTILINE）
　　默认情况下，PCRE 将目标字符串作为单一的一“行”字符所组成的（甚至其中包含有换行符也是如此）。“行起始”元字符（^）仅仅匹配字符串的起始，“行结束”元字符（，则设定此修正符没有任何效果。
s：（PCRE_DOTALL）
　　如果设定了此修正符，模式中的圆点元字符（.）匹配所有的字符，包括换行符（一般情况下‘.’是不能匹配‘\n’的）。没有此设定的话，则不包括换行符。这和 Perl 的 /s 修正符是等效的。排除字符类例如 [^a] 总是匹配换行符的，无论是否设定了此修正符。
x：（PCRE_EXTENDED）
　　如果设定了此修正符，模式中的空白字符除了被转义的或在字符类中 的以外完全被忽略，在未转义的字符类之外的 # 以及下一个换行符之间的所有字符，包括两头，也都被忽略。这和 Perl 的 /x 修正符是等效的，使得可以在复杂的模式中加入注释。然而注意，这仅适用于数据字符。空白字符可能永远不会出现于模式中的特殊字符序列，例如引入条件子模式 的序列 (?( 中间。
e：
　　如果设定了此修正符，preg_replace() 在替换字符串中对逆向引用作正常的替换，将其作为 PHP 代码求值，并用其结果来替换所搜索的字符串。
　　只有 preg_replace() 使用此修正符，其它 PCRE 函数将忽略之。
　　注: 本修正符在 PHP3 中不可用。
A：（PCRE_ANCHORED）
　　如果设定了此修正符，模式被强制为“anchored”，即强制仅从目标字符串的开头开始匹配。此效果也可以通过适当的模式本身来实现（在 Perl 中实现的唯一方法）。
D：（PCRE_DOLLAR_ENDONLY）
　　如果设定了此修正符，模式中的美元元字符仅匹配目标字符串的结尾。没有此选项时，如果　　最后一个字符是换行符的话，美元符号也会匹配此字符之前（但不会匹配任何其它换行符之前）。如果设定了 m 修正符则忽略此选项。Perl 中没有与其等价的修正符。
S：
　　当一个模式将被使用若干次时，为加速匹配起见值得先对其进行分析。如果设定了此修正符则会进行额外的分析。目前，分析一个模式仅对没有单一固定起始字符的 non-anchored 模式有用。
U：（PCRE_UNGREEDY）
　　本修正符反转了匹配数量的值使其不是默认的重复，而变成在后面跟上“?”才变得重复。这和 Perl 不兼容。也可以通过在模式之中设定 (?U) 修正符或者在数量符之后跟一个问号（如 .*?）来启用此选项。
X（PCRE_EXTRA）
　　此修正符启用了一个 PCRE 中与 Perl 不兼容的额外功能。模式中的任何反斜线后面跟上一个没有特殊意义的字母导致一个错误，从而保留此组合以备将来扩充。默认情况下，和 Perl 一样，一个反斜线后面跟一个没有特殊意义的字母被当成该字母本身。当前没有其它特性受此修正符控制。
 */

class TextSearch
{

    protected static function escape($str){
        return str_replace(['/', '.', '?', '*', '+'], ['\/', '\.', '\?', '\*', '\+'], $str);
    }
    /**
     * 检索在以某字符开始和结束的内容
     * @param string $body
     * @param string $startText
     * @param string $endText
     * @return void
     */
    public static function getBetweenContent(string $body, string $startText, string $endText, $returnType = 1){
        // .*? 匹配任意字符 0次或者多次 非贪婪匹配
        $pattern =  '/'.self::escape($startText).'(.*?)'.self::escape($endText).'/is'; // 需要走最少的匹配也就是非贪婪
        preg_match_all($pattern,
            $body,
            $outs);

        if($returnType < 0){
            return $outs;
        }

        return $outs[$returnType] ?? [];
    }

    /**
     * 获取所有的href连接地址
     * @param string $body
     * @return void
     */
    public static function getHref(string $body){
        $pattern = "/href=['|\"](.*?)['|\"]/is"; // 括号发挥了作用 是用来分组的
        preg_match_all($pattern,
            $body,
            $outs);
        return $outs[1];
    }
}