<?php
define("ROOT", dirname(__FILE__));

require_once ROOT."/vendor/autoload.php";

use \PhpParser\ParserFactory;
use PhpParser\Error;

$content = file_get_contents(ROOT."/test/exampleRoute.php");
$parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
$addRouteList =
$groupList = [];
try {
    $ast = $parser->parse($content);
    $routeObj = new RouteGroup();
    $addRouteObj = new RouteOfAddRoute();
    foreach ($ast as $item) {
        $expr = $item->expr;
        $attrs = $expr->class->getAttributes();
        switch ($expr->name->name) {
            case 'addRoute':
                $addRouteList[] = $addRouteObj->parseOneAddRoute($expr);
                break;
            case 'post':
            case 'get':
                break;
            case 'addGroup':
                $routePrefix = $expr->args[0]->value->value;
                // 若干个 $expr->args[1]->value->stmts[0]
                if (is_array($expr->args[1]->value->stmts)) {
                    $routeList   = $routeObj->parseOneGroup($expr->args[1]->value->stmts, $routePrefix);
                    $groupList[] = $routeList;
                }
                break;
            default:
                throw new \Exception("不支持的 route 表达式：{$expr->name->name}", -3);
        }
    }
} catch (Error $error) {
    echo "Parse error: {$error->getMessage()}\n";
}

$routeListJson = json_encode($groupList, 320);
file_put_contents('/Users/suhanyu/www/tmp.txt', $routeListJson);
echo "\n---------------------------------------------------------\n";
echo json_encode($addRouteList, 320);

/**
 * 处理 Router addGroup
 * Class RouteGroup
 */
class RouteGroup
{
    /**
     * 解析一个RouteGroup
     * @throws Exception
     */
    public function parseOneGroup(array $stmts, string $routePrefix): array
    {
        if (empty($stmts)) return [];
        $list = [];
        $addRouteObj = new RouteOfAddRoute();
        foreach ($stmts as $stmt) {
            switch ($stmt->expr->name->name) {
                case 'addRoute':
                    $list[] = $addRouteObj->parseOneAddRoute($stmt->expr, $routePrefix);
                    break;
                case 'post':
                case 'get':
                    $list[] = $this->parsePostOrGet($stmt, $routePrefix);
                    break;
                default:
                    throw new \Exception("不支持的 group 内的路由表达式", -2);
            }
        }
        return $list;
    }

    /**
     * 解析 Route::post()/Route::get()
     */
    public function parsePostOrGet($stmt, string $routePrefix)
    {
        $tmpClass = $stmt->expr->class;
        if (empty($tmpClass->getAttributes()['comments'])) {
            throw new \Exception("路由缺少注释", -1);
        }
        $apiDesc = $tmpClass->getAttributes()['comments'][0]->getText();
        $apiDesc = trimDesc($apiDesc);
        $method = $this->parseMethods($stmt);
        $partUri = $stmt->expr->args[0]->value->value;
        return [
            'method' => $method,
            'uri'    => "{$routePrefix}{$partUri}",
            'desc'   => $apiDesc,
        ];
    }

    /**
     * 解析出请求方法
     */
    public function parseMethods($stmt): string
    {
        $method = $stmt->expr->name->name;

        return strtoupper($method);
    }
}

class RouteOfAddRoute
{
    /**
     * 解析 AddRoute
     */
    public function parseOneAddRoute($expr, string $routePrefix = '')
    {
        $methods = $this->parseMethod($expr->args[0]->value->items);
        $desc = $this->parseComment($expr->getAttributes());
        $uri = $this->parseUri($expr->args[1]);
        return [
            'method' => $methods[0] ?? 'unknow',
            'uri'    => "{$routePrefix}{$uri}",
            'desc'   => $desc,
        ];
    }

    /**
     * 解析 method
     */
    public function parseMethod(array $methodArgItems)
    {
        $methods = [];
        foreach ($methodArgItems as $item) {
            $methods[] = $item->value->value;
        }
        return $methods;
    }

    /**
     * 解析 comment
     */
    public function parseComment(array $attrs)
    {
        $comments = $attrs['comments'] ?? [];
        $comment = $comments[0] ?? [];
        if (empty($comment)) {
            return '';
        }
        $desc = $comment->getText();
        $desc = trimDesc($desc);

        return $desc;
    }

    /**
     * 解析路由
     */
    public function parseUri(PhpParser\Node\Arg $arg)
    {
        return $arg->value->value;
    }
}

// 清理注释中的注释字符
function trimDesc($desc = ''): string
{
    return str_replace(["//", '//  ', '// ', ' '], '', $desc);
}

