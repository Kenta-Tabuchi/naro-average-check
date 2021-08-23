<!DOCTYPE html>
<html lang="ja">
<head>
    <title>なろう平均チェッカー</title>
    <link href="./average.css" rel="stylesheet">
    <style>
        td{
            background:snow !important;
        }
    </style>
</head>

<body>
<div class="main">
    <h1>なろう平均チェッカー</h1>
<?php

//****************変数定義****************

$rank_count = 0; //ランキング読み込みループ切り上げ用のループカウンタ
$rank_count_max = 100; //何位まで読み込むかの最大値

$max_title = 0; //タイトル文字数の最大
$max_title_word = 0; //タイトル単語数の最大
$count_title = 0; //タイトル文字数の合計
$count_title_word = 0; //タイトル単語数の合計
$count_word = 0; //平均文字数の合計
$count_interval = 0; //平均投稿間隔の合計
$count_hb = 0;//評価者ブクマ者比の合計
$count_num = $rank_count_max;//平均の平均データを取る時の母数

$array_hb =array(0,0,0,0,0,0,0,0,0,0,0);//評価者ブクマ者比の出現回数
$array_length =array(0,0,0,0,0,0,0,0,0,0,0);//平均文字数の出現回数
$array_interval =array(0,0,0,0,0,0,0,0,0,0,0,0,0);//平均投稿間隔の出現回数

require_once './igo/Igo.php';  //形態素解析ツールigoのアドレス指定
$igo = new Igo("./ipadic", "UTF-8"); //辞書フォルダのアドレス指定

//****************本文開始***************

print '日付：'.$_GET['day'].'</br>';
print '対象：';
switch ($_GET['type']){
    case 'd':
        print '日間';
        break;
    case 'w':
        print '週間';
        break;        
    case 'm':
        print '月間';
        break;
    case 'q':
        print '四半期';
        break;
    default:
        print 'クエリがありません';
    }
print '</br>';
print '取得対象:'. $rank_count_max .'位まで</br>';
print '</br>';

//指定された日付の日刊ランキング取得
//APIのURL(パラメーターを指定してください)
$url='https://api.syosetu.com/rank/rankget/?out=json&gzip=5&rtype='.$_GET['day'].'-'.$_GET['type'];

//ヘッダーの設定
$options =array(
    'http' =>array(
            'header' => "User-Agent:".$_SERVER['HTTP_USER_AGENT'],
            )
    );

//APIを取得
$file = file_get_contents($url, false, stream_context_create($options));

//解凍する
$file=gzdecode($file);

//JSONデコード
$listarray=json_decode($file,true);

//****************テーブルを作成****************
print '<table border="1" width="100%" style="table-layout:fixed">';
print '<tr><th>タイトル</th><th>初投稿日</th><th>現在話数</br>現在文字数</th><th>現在P</br>レビュー数</th><th>会話率</th><th>現在評価者数</br>現在ブクマ者数</th>
<th>評価者ブクマ者比</th><th>タイトル文字数</br>タイトル単語数</th><th>最新投稿まで日数</th><th>平均文字数</th><th>平均投稿間隔</th>
<th>P／話数</th><th>1人辺りP</th>';

//読み込んだデータを解析する
foreach($listarray as $key=>$value){
    $rank_count++;
    if($rank_count>$rank_count_max){break;}; //指定した順位まで読み込んだら切り上げ

    //nコードからタイトルとあらすじを取得
    $now_url='https://api.syosetu.com/novelapi/api/?out=json&gzip=5&ncode='.$value['ncode'];

    //APIを取得
    $now_file = file_get_contents($now_url, false, stream_context_create($options));

    //解凍する
    $now_file=gzdecode($now_file);

    //JSONデコード
    $now_listarray=json_decode($now_file,true);

    //作品ごとにチェック
    foreach($now_listarray as $now_key=>$now_value){
        //nコードの存在しない小説の場合要素0に[{"allcount":0}]と入っているのでそれをチェック
        if($now_key==0){
            if($now_value['allcount']=='0'){
                //存在しなかった場合母数を減らす
                $count_num-=1;
                print '<tr><td>＊存在しません＊</td></tr>';

                //デバッグ用：画面出力
                //print '<b>'.$value['rank']."位</b><br>";
                //print ('この小説は存在しません');
                break;
            }
        }else{
            //要素0はcontinueで飛ばす
            if($now_key==0){continue;}
                //初投稿日取得
                $firstup=str_split($now_value['general_firstup'],10);
                //最終投稿日取得
                $lastup=str_split($now_value['general_lastup'],10);
                //話数取得
                $allno=$now_value['general_all_no'];
                //文字数取得
                $length=$now_value['length'];
                //ポイント取得
                $point=$now_value['global_point'];

                //行作成
                print '<tr><td width="20%">';
                //タイトル
                print '<b><a href="https://ncode.syosetu.com/' .$value['ncode']. '" target="_blank" rel="noopener noreferrer">'.$now_value['title'].'</a></b></td>';
                //初投稿日
                print '<td>'.$firstup[0].'</td>';
                //現在話数
                print '<td>'.$allno.'話'.'</br>';
                //現在文字数
                print $length.'文字'.'</td>';
                //現在ポイント
                print '<td>'.$point.'P'.'</br>';
                //レビュー数
                print $now_value['review_cnt'].'件'.'</br>';
                //会話率
                print '<td>'.$now_value['kaiwaritu'].'％'.'</td>';
                //現在評価者数
                print '<td>'.$now_value['all_hyoka_cnt'].'人'.'</br>';
                //現在ブクマ者数
                print $now_value['fav_novel_cnt'].'人'.'</td>';
                //評価者ブクマ者比
                $hb=round($now_value['fav_novel_cnt']/$now_value['all_hyoka_cnt'] ,2);
                print '<td>'.$hb.'</td>';
                //タイトル文字数
                print '<td>'.mb_strlen($now_value['title']).'文字'.'</br>';
                //タイトル単語数
                $result = $igo->wakati($now_value['title']);
                print count($result).'単語'.'</td>';
                //最新投稿まで日数
                $day1 = new DateTime($firstup[0]);
                $day2 = new DateTime($lastup[0]);
                $interval_day = $day2 -> diff($day1);
                $interval = $interval_day->format('%a');
                print '<td>'.$interval.'日'.'</td>';
                //平均文字数
                $av_length=round($length / $allno , 1);
                print '<td>'.$av_length.'文字'.'</td>';
                //平均投稿間隔
                $av_interval=round($interval / $allno , 1);
                print '<td>'.$av_interval.'日'.'</td>';
                //ポイント／話数
                print '<td>'.round($point / $allno,1).'</br>';
                //一人辺りのポイント推定
                $h_nom=($point - 2*$now_value['fav_novel_cnt'])/$now_value['all_hyoka_cnt'];
                print '<td>'.round($h_nom , 1).'</td>';
                print '</td>';

                //連載の場合合計データに加算
                if($now_value['novel_type']==1){
                    //最大値
                    if($max_title < mb_strlen($now_value['title'])){
                        $max_title=mb_strlen($now_value['title']);
                    }
                    if($max_title_word<count($result)){
                        $max_title_word=count($result);
                    }
                    //平均値
                    $count_title+=mb_strlen($now_value['title']);
                    $count_title_word+=count($result);
                    $count_word+=$av_length;
                    $count_interval+=$av_interval;
                    $count_hb+=$hb;

                    //出現値
                    if($hb<1.0){ //評価者ブクマ者比率
                        $array_hb[0]+=1;
                    }else if(1<=$hb&&$hb<2){
                        $array_hb[1]+=1;
                    }else if(2<=$hb&&$hb<3){
                        $array_hb[2]+=1;
                    }else if(3<=$hb&&$hb<4){
                        $array_hb[3]+=1;
                    }else if(4<=$hb&&$hb<5){
                        $array_hb[4]+=1;
                    }else if(5<=$hb&&$hb<6){
                        $array_hb[5]+=1;
                    }else if(6<=$hb&&$hb<7){
                        $array_hb[6]+=1;
                    }else if(7<=$hb&&$hb<8){
                        $array_hb[7]+=1;
                    }else if(8<=$hb&&$hb<9){
                        $array_hb[8]+=1;
                    }else if(9<=$hb&&$hb<10){
                        $array_hb[9]+=1;
                    }else if(10<=$hb){
                        $array_hb[10]+=1;
                    }
                    if($av_length<1000){ //平均文字数
                        $array_length[0]+=1;
                    }else if(1000<=$av_length&&$av_length<200){
                        $array_length[1]+=1;
                    }else if(2000<=$av_length&&$av_length<3000){
                        $array_length[2]+=1;
                    }else if(3000<=$av_length&&$av_length<4000){
                        $array_length[3]+=1;
                    }else if(4000<=$av_length&&$av_length<5000){
                        $array_length[4]+=1;
                    }else if(5000<=$av_length&&$av_length<6000){
                        $array_length[5]+=1;
                    }else if(6000<=$av_length&&$av_length<7000){
                        $array_length[6]+=1;
                    }else if(7000<=$av_length&&$av_length<8000){
                        $array_length[7]+=1;
                    }else if(8000<=$av_length&&$av_length<9000){
                        $array_length[8]+=1;
                    }else if(9000<=$av_length&&$av_length<10000){
                        $array_length[9]+=1;
                    }else if(10000<=$av_length){
                        $array_length[10]+=1;
                    }
                    if($av_interval<1){ //平均投稿間隔
                        $array_interval[0]+=1;
                    }else if(1<=$av_interval&&$av_interval<=2){
                        $array_interval[1]+=1;
                    }else if(2<$av_interval&&$av_interval<=3){
                        $array_interval[2]+=1;
                    }else if(3<$av_interval&&$av_interval<=4){
                        $array_interval[3]+=1;
                    }else if(4<$av_interval&&$av_interval<=5){
                        $array_interval[4]+=1;
                    }else if(5<$av_interval&&$av_interval<=6){
                        $array_interval[5]+=1;
                    }else if(6<$av_interval&&$av_interval<=7){
                        $array_interval[6]+=1;
                    }else if(7<$av_interval&&$av_interval<=14){
                        $array_interval[7]+=1;
                    }else if(14<$av_interval&&$av_interval<=21){
                        $array_interval[8]+=1;
                    }else if(21<$av_interval&&$av_interval<=30){
                        $array_interval[9]+=1;
                    }else if(30<$av_interval){
                        $array_interval[10]+=1;
                    }

                }else{
                    //読み切りの場合母数を減らす
                    $count_num-=1;
                }

                //デバッグ用：画面出力
                //print nl2br($now_value['keyword'])."<br>";

                //デバッグ用：小説情報の画面出力
                /*
                print '<b>'.$value['rank']."位</b><br>"; //ランク
                print '<b>'.$now_value['title']."</b><br>"; //タイトル
                print nl2br($now_value['story'])."<br>"; //あらすじ
                print nl2br($now_value['keyword'])."<br>"; //キーワード
                $novelurl='http://ncode.syosetu.com/'.strtolower($value['ncode']).'/'; //URL
                print '<a href="'.$novelurl.'">'.$novelurl.'</a>'; //小説へのリンク
                */
        }
        //デバッグ用：画面出力
        //print '<hr>';
    }
}

print '</table>';
print '</br>';

print '＊以下のデータは読み切りのデータを含んでいません。ご了承ください。</br></br>';
print '<table border="1" style="table-layout:fixed">';
print '<tr><th>連載作品数</th><th>最大タイトル長</th><th>最大タイトル単語数</th>
<th>平均タイトル長</th><th>平均タイトル単語数</th><th>平均平均文字数</th><th>平均平均投稿間隔</th><th>平均評価者ブクマ者比</th></tr>';
print '<tr>';
//連載作品数
print '<td>'.$count_num.'</td>';
//最大タイトル長さ
print '<td>'.$max_title.'文字'.'</td>';
//平均タイトル単語数
print '<td>'.$max_title_word.'単語'.'</td>';
//平均タイトル長
print '<td>'.round($count_title / $count_num , 2).'文字'.'</td>';
//平均タイトル単語数
print '<td>'.round($count_title_word / $count_num , 2).'単語'.'</td>';
//平均平均文字数
print '<td>'.round($count_word / $count_num , 2).'文字'.'</td>';
//平均投稿間隔
print '<td>'.round($count_interval / $count_num , 2).'日'.'</td>';
//平均評価者ブクマ者比
print '<td>'.round($count_hb / $count_num , 2).'</td>';
print '</tr>';
print '</table>';

print '＊＊＊＊評価者ブクマ者比表＊＊＊＊';
$array_hb_label=array("1未満",
"1以上",
"2以上",
"3以上",
"4以上",
"5以上",
"6以上",
"7以上",
"8以上",
"9以上",
"10以上");
print '<table border="1" width="50%">';
for($x=0;$x<=10;$x++){
    print '<tr><td width="20%" style="word-wrap:break-word;">'.$array_hb_label[$x].'</td>';
    print '<td width="5%">'.$array_hb[$x].'</td>';
    printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $array_hb[$x] / $count_num * 100);
    print '</tr>';
}
print '</table>';
print '</br>';

print '＊＊＊＊平均文字数表＊＊＊＊</br>';
$array_length_label=array("1000文字未満",
"1000文字以上",
"2000文字以上",
"3000文字以上",
"4000文字以上",
"5000文字以上",
"6000文字以上",
"7000文字以上",
"8000文字以上",
"9000文字以上",
"10000以上");
print '<table border="1" width="50%">';
for($x=0;$x<=10;$x++){
    print '<tr><td width="20%" style="word-wrap:break-word;">'.$array_length_label[$x].'</td>';
    print '<td width="5%">'.$array_length[$x].'</td>';
    printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $array_length[$x] / $count_num * 100);
    print '</tr>';
}
print '</table>';
print '</br>';

print '＊＊＊＊平均投下間隔表＊＊＊＊</br>';
$array_interval_label=array("1日未満",
"2日以内",
"3日以内",
"4日以内",
"5日以内",
"6日以内",
"7日以内",
"2週間以内",
"3週間以内",
"1ヶ月以内",
"1ヶ月以上");
print '<table border="1" width="50%">';
for($x=0;$x<=10;$x++){
    print '<tr><td width="20%" style="word-wrap:break-word;">'.$array_interval_label[$x].'</td>';
    print '<td width="5%">'.$array_interval[$x].'</td>';
    printf("<td><hr size=\"10\" color=\"#cc6633\" align=\"left\" width=\"%d%%\"></td>", $array_interval[$x] / $count_num * 100);
    print '</tr>';
}
print '</table>';
print '</br>';

print '<u><a href="./top.php">トップへ戻る</a></u>';
?>
</div>
</body>