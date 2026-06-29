<?php
session_start();
include("config.php");
$arr = user_shell($_SESSION['uid'],$_SESSION['user_shell']); //权限判断
//对权限进行判断
/**
 * @
 * @Description:
 * @Copyright (C) 2011 helloweba.com,All Rights Reserved.
 * -----------------------------------------------------------------------------
 * @author: Liurenfei (lrfbeyond@163.com)
 * @Create: 2012-5-1
 * @Modify:
 */
$action = $_GET['action'];
if ($action == 'import') {
    //导入CSV
    $filename = $_FILES['file']['tmp_name'];
    if (empty ($filename)) {
        echo "<meta charset='utf-8'>";
        echo "<script type='text/javascript'>alert('请选择文件！');window.location='search.php';</script>";
        exit;
    }
    $handle = fopen($filename, 'r');
    $result = input_csv($handle);
    //解析csv
    $len_result = count($result);
    if ($len_result == 0) {
        echo "<meta charset='UTF-8'>";
        echo "<script type='text/javascript'>alert('你的文件没有任何数据！');window.location='search.php';</script>";
        exit;
    }
    $imported = 0;
    $query = false;
    for ($i = 1; $i < $len_result; $i++) {
        //循环获取各字段值
        $time100 = strtotime($result[$i][0]);
        $name = mb_convert_encoding($result[$i][1],'utf-8','utf-8');
        $category = mb_convert_encoding($result[$i][2],'utf-8','utf-8');
		$note = mb_convert_encoding($result[$i][3],'utf-8','utf-8');
        $place = mb_convert_encoding($result[$i][4],'utf-8','utf-8');
        $setpayway = mb_convert_encoding($result[$i][5],'utf-8','utf-8');
		$amount = $result[$i][6];
        $special = $result[$i][7];
        $getcashflow = mb_convert_encoding($result[$i][8],'utf-8','utf-8');
        $acuserid = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;
        if ($time100 === false || !is_numeric($amount) || !ctype_digit((string)$special)) {
            $query = false;
            break;
        }
        $amount = (float)$amount;
        $special = (int)$special;
        


        if ($getcashflow == "收入") {
            $cashflow = "1";
        } else {
            $cashflow = "2";
        }
			
		$sqlpay = "select payid from ".$prename."account_payway where paywayname=? and ufid=? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sqlpay);
        mysqli_stmt_bind_param($stmt, "si", $setpayway, $acuserid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $acpayway);
        $attitle = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if (!$attitle) {
            $sqladd = "insert into ".$prename."account_payway (paywayname,ufid) values (?, ?)";
            $stmt = mysqli_prepare($conn, $sqladd);
            mysqli_stmt_bind_param($stmt, "si", $setpayway, $acuserid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $acpayway = mysqli_insert_id($conn);
        } else {
            $acpayway = (int)$acpayway;
        }

        $sqlcategory = "select categoryid from ".$prename."category where categoryname=? and ufid=? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sqlcategory);
        mysqli_stmt_bind_param($stmt, "si", $category, $acuserid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $accategory);
        $attitle = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
		 if (!$attitle) {
            $sqladd = "insert into ".$prename."category (categoryname,ufid) values (?, ?)";
            $stmt = mysqli_prepare($conn, $sqladd);
            mysqli_stmt_bind_param($stmt, "si", $category, $acuserid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $accategory = mysqli_insert_id($conn);
        } else {
            $accategory = (int)$accategory;
        }

        $sqlaccount = "insert into ".$prename."account (accategory,acpayway,acamount,actime,acremark,acuserid,acplace,acname,ac0,ac1,acclassid,ac2) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
        $stmt = mysqli_prepare($conn, $sqlaccount);
        mysqli_stmt_bind_param($stmt, "iidisissiii", $accategory, $acpayway, $amount, $time100, $note, $acuserid, $place, $name, $special, $cashflow, $cashflow);
        $query = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if (!$query) {
            break;
        }
        $imported++;
    }
    fclose($handle);
    //关闭指针
    if ($query && $imported > 0) {
        echo "<meta charset='UTF-8'>";
        $d = "导入成功！导入了";
        $e = " 条！";
        $f = $d.$imported.$e;
        echo "<script type='text/javascript'>alert('$f');window.location='search.php';</script>";
    } else {
        echo "<meta charset='UTF-8'>";
        $c = "导入失败，请检查文件格式！";
        echo "<script type='text/javascript'>alert('$c');window.location='search.php';</script>";
    }
} elseif ($action == 'export') {
    //导出CSV
    $result = mysqli_query($conn,"select acamount, accategory, acplace, actime, acremark, acpayway, acname, ac0, ac1  from ".$prename."account where acuserid='$_SESSION[uid]'");
    $str = "时间,交易对象,分类,备注,位置,支付方式,金额,特殊,收支\n";
    $str = iconv('utf-8','utf-8',$str);
    while ($row = mysqli_fetch_array($result)) {
        
		$sqlpay = "select * from ".$prename."account_payway where payid=$row[acpayway] and ufid='$_SESSION[uid]'";
        $payquery = mysqli_query($conn,$sqlpay);
        $payinfo = mysqli_fetch_array($payquery);
        $setpayway = iconv('utf-8','utf-8//IGNORE',$payinfo['paywayname']);
        
        $sqlcategory = "select * from ".$prename."category where categoryid=$row[accategory] and ufid='$_SESSION[uid]'";
        $categoryquery = mysqli_query($conn,$sqlcategory);
        $categoryinfo = mysqli_fetch_array($categoryquery);
		$category = iconv('utf-8','utf-8',$categoryinfo['categoryname']);
		
        if ($row['ac1'] == 1) {
            $cashflow = iconv('utf-8','utf-8',"收入");
        } else {
            $cashflow = iconv('utf-8','utf-8',"支出");
        }
        $amount = $row['acamount'];
		$special = $row['ac0'];
	    $place = iconv('utf-8','utf-8',$row['acplace']);
		$name = iconv('utf-8','utf-8//IGNORE',$row['acname']);
        $paytime = date("Y-m-d H:i",$row['actime']);
        $note = iconv('utf-8','utf-8',$row['acremark']);
        $str .= $paytime.",".$name.",".$category.",".$note.",".$place.",".$setpayway.",".$amount.",".$special.",".$cashflow."\n";
    }
    $filename = date('Ymd').'.csv';
    export_csv($filename,$str);
}
function input_csv($handle) {
    $out = array ();
    $n = 0;
    while ($data = fgetcsv($handle, 10000)) {
        $num = count($data);
        for ($i = 0; $i < $num; $i++) {
            $out[$n][$i] = $data[$i];
        }
        $n++;
    }
    return $out;
}

function export_csv($filename,$data) {
    header("Content-type:text/csv");
    header("Content-Disposition:attachment;filename=".$filename);
    header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
    header('Expires:0');
    header('Pragma:public');
    echo $data;
}
