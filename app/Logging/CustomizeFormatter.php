<?php


namespace App\Logging;



use Illuminate\Support\Facades\Auth;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;

class CustomizeFormatter
{
    private $dateFormat = 'Y-m-d H:i:s.v';

    /**
     * 渡されたロガーインスタンスのカスタマイズ
     *
     * @param  \Illuminate\Log\Logger  $logger
     * @return void
     */
    public function __invoke($logger)
    {
        // フォーマットの修正がしやすいように配列を用いる
        // 参考 https://qiita.com/_hiro_dev/items/cea556897a36fcec31bf#1-%E3%82%AB%E3%82%B9%E3%82%BF%E3%83%A0%E3%83%95%E3%82%A9%E3%83%BC%E3%83%9E%E3%83%83%E3%82%BF%E3%81%AE%E6%8C%87%E5%AE%9A
        $format = ''.
            implode(', ', [
                '["%datetime%"]',
                '%channel%.%level_name%',
//                '"memoryUsage": "%extra.memory_usage%"',
                '%extra.class%#%extra.function%',
                '%extra.line%行目',
                '"%message%"',
                '%extra.ip%',
                '"userId": "%extra.userid%"',
                '"userName": "%extra.username%"',
                '"%context%"',
                '"%extra.uid%"',
            ])
            .''.PHP_EOL;

        // ログのフォーマットと日付のフォーマットを指定する
        $lineFormatter = new LineFormatter($format, $this->dateFormat, true, true);
        // IntrospectionProcessorを使うとextraフィールドが使えるようになる
        $ip = new IntrospectionProcessor(Logger::DEBUG, ['Illuminate\\']);
        // WebProcessorを使うとextra.ipが使えるようになる
        $wp = new WebProcessor();
        // MemoryUsageProcessorを使うとextra.memory_usageが使えるようになる
        $mup = new MemoryUsageProcessor();

        // uuidを使うと同一リクエストを把握できる(セッションではない) (https://www.zu-min.com/archives/567)
        $uidProcessor = new UidProcessor();

        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter($lineFormatter);
            // pushProcessorするとextra情報をログに埋め込んでくれる
            $handler->pushProcessor($ip);
            $handler->pushProcessor($wp);
            $handler->pushProcessor($mup);
            $handler->pushProcessor($uidProcessor);
            // addExtraFields()を呼び出す。extra.useridとextra.usernameをログに埋め込んでくれる
            $handler->pushProcessor([$this, 'addExtraFields']);
        }

    }

    public function addExtraFields(array $record): array
    {
        $user = Auth::user();
        $record['extra']['userid'] = $user->id ?? null;
        $record['extra']['username'] = $user ? $user->name : '未ログイン';
        return $record;
    }
}
