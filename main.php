<?php 
 
// composerライブラリー
require __DIR__ . '/vendor/autoload.php';
use Goutte\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
 
 
// URL
$top_url    = 'http://www.mapion.co.jp/phonebook/M06001/10/';
 
// ログ情報
$log_name   = 'mapion';
$log_file   = 'logs/my-log.log';
 
// SCVで結果を出力
$csv_file   = 'my-final-file.csv';
 
 
// アクセスされたページ
$details_crawled =[];
 
// ログインスタンス化
$log = new Logger($log_name);
$log->pushHandler(new StreamHandler($log_file, Logger::INFO));
 
//　温泉情報
$infos = array();
 
// 各市区町村のURL
$list = [];
$client = new Client();
 
// Goutterインスタンス
$crawler = $client->request('GET', $top_url );
 
// DOMエレメントをループ
$crawler->filter('ul.lists > li > a')->each(function($node) use(&$list){
    $list[$node->text()]=$node->attr('href');
});
 
//ページループ
foreach ($list as $url_name => $url_to_check){
 
    // ログ出力
    $log->addWarning('現在のページ : ' . $url_name);
 
    // Goutteでリクエスト
    $crawler = $client->request('GET', $url_to_check );
 
    // 次のページがあるかどうか
    $is_last_page = false;
 
    // 今のページ番号
    $current_page = 1;
 
    do {
 
        // テーブルの最初の4行は不要
        $count = 0 ;
 
        // current_page
        $crawler->filter('table.list-table tr')->each(function ($node) use ($client,$crawler, &$infos, &$log, &$details_crawled, &$count, &$url_name) {
 
            $count++ ;
 
            // 現在の温泉情報
            $local_infos = [];
 
            if($count>1){
                $local_infos[] = $url_name;
                $local_infos[]=trim(str_replace("　","",$node->filter('th')->first()->text()));
                $local_infos[]=trim(str_replace("　","",$node->filter('td')->last()->text()));
                array_push($infos,$local_infos);
            }
 
        });
 
        // ページのincrementation
        $current_page ++ ;
 
     try{
 
        // 次のページを取得
        $link = $crawler->selectLink(strval($current_page))->link();
        $next_page_crawler  = $client->click($link);
 
        // 次のページへ移動
        $crawler = $next_page_crawler;
 
    }catch(InvalidArgumentException $e){
        $log->addWarning('最後のページでした : ');
        $is_last_page = true;
    }catch(Exception $e){
        $log->addError('例外が発生しました ! '. $e);
        $is_last_page = true;
    }
 
    } while ($is_last_page == false);
 
}
 
 
// csvファイルを生成
$scv_file_name = "generated". DIRECTORY_SEPARATOR . $csv_file ;
 
$fp = fopen($scv_file_name, 'w');
foreach ($infos as $fields) {
    $comma_separated = implode(",", $fields);
    fwrite($fp, $comma_separated);
    fwrite($fp, PHP_EOL);
}
fclose($fp);
 
print "終了！";
 
?>