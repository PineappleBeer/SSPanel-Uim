<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Services\Subscribe;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use App\Services\DB;

final class ServerController extends BaseController
{
    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $nodes = Subscribe::getUserNodes($this->user, true);
        $node_list = [];

        foreach ($nodes as $node) {
            $logs = DB::select("
            SELECT
                id,
                node_id,
                last_time
            FROM
                online_log
            WHERE
                node_id = '{$node->id}'
                AND last_time > UNIX_TIMESTAMP() - 90
			");

            $count = count($logs);
            $node_list[] = [
                'id' => $node->id,
                'name' => $node->name,
                'class' => (int) $node->node_class,
                'color' => $node->color,
                'sort' => $node->sort(),
                'online_user' => $count,
                'online' => $node->getNodeOnlineStatus(),
                'traffic_rate' => $node->traffic_rate,
                'is_dynamic_rate' => $node->is_dynamic_rate,
                'node_bandwidth' => Tools::autoBytes($node->node_bandwidth),
                'node_bandwidth_limit' => $node->node_bandwidth_limit === 0 ? '无限制' :
                    Tools::autoBytes($node->node_bandwidth_limit),
            ];
        }

        return $response->write(
            $this->view()
                ->assign('servers', $node_list)
                ->fetch('user/server.tpl')
        );
    }
}
