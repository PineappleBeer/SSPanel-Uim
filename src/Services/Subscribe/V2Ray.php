<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Models\Config;
use App\Services\Subscribe;
use function base64_encode;
use function json_decode;
use function json_encode;
use const PHP_EOL;

final class V2Ray extends Base
{
    public function getContent($user): string
    {
        $links = '';
        //判断是否开启V2Ray订阅
        if (!Config::obtain('enable_v2_sub')) {
            return $links;
        }

        $nodes_raw = Subscribe::getUserNodes($user);

        foreach ($nodes_raw as $node_raw) {
            $node_custom_config = json_decode($node_raw->custom_config, true);
            //檢查是否配置“前端/订阅中下发的服务器地址”
            if (!array_key_exists('server_user', $node_custom_config)) {
                $server = $node_raw->server;
            } else {
                $server = $node_custom_config['server_user'];
            }
            if ((int) $node_raw->sort === 11) {
                $v2_port = $node_custom_config['offset_port_user'] ?? ($node_custom_config['offset_port_node'] ?? 443);
                $security = $node_custom_config['security'] ?? 'none';
                $sni = $node_custom_config['sni'] ?? '';
                $pbk = $node_custom_config['pbk'] ?? '';
                $flow = $node_custom_config['flow'] ?? '';
                $transit_address = $node_custom_config['transit_address'] ?? '';
                $transit_port = $node_custom_config['transit_port'] ?? '';
                $network = $node_custom_config['network'] ?? '';
                $header = $node_custom_config['header'] ?? ['type' => 'none'];
                $header_type = $header['type'] ?? '';
                $host = $node_custom_config['header']['request']['headers']['Host'][0] ?? $node_custom_config['host'] ?? '';
                $path = $node_custom_config['header']['request']['path'][0] ?? $node_custom_config['path'] ?? '/';

                $v2rayn_array = [
                    'v' => '2',
                    'ps' => $node_raw->name,
                    'add' => $node_raw->server,
                    'port' => $v2_port,
                    'id' => $user->uuid,
                    'aid' => 0,
                    'net' => $network,
                    'type' => $header_type,
                    'host' => $host,
                    'path' => $path,
                    'tls' => $security,
                ];

                if (($node_custom_config['enable_vless'] ?? '0') === '1') {
                    if (($node_custom_config['enable_transit'] ?? '0') === '1') {
                        $links .= 'vless://' . $user->uuid . '@' . $transit_address . ':' . $transit_port . '?encryption=none&flow=' . $flow . '&security=reality&sni=' . $sni . '&fp=chrome&pbk=' . $pbk . '&type=tcp&headerType=none#' . $node_raw->name . PHP_EOL;
                    } else {
                        $links .= 'vless://' . $user->uuid . '@' . $server . ':' . $v2_port . '?encryption=none&flow=' . $flow . '&security=reality&sni=' . $sni . '&fp=chrome&pbk=' . $pbk . '&type=tcp&headerType=none#' . $node_raw->name . PHP_EOL;
                    }
                } else {
                    $links .= 'vmess://' . base64_encode(json_encode($v2rayn_array, 320)) . PHP_EOL;
                }
            }
        }

        return $links;
    }
}
