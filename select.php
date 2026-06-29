<?php
error_reporting(E_ALL ^ E_NOTICE);
session_start();
    include("config.php");
    $arr = user_shell($_SESSION['uid'], $_SESSION['user_shell']);
    //对权限进行判断

$ctype = isset($_GET["ctype"]) ? $_GET["ctype"] : "";
$select = array();
if ($ctype !== "") {
    if (!ctype_digit((string)$ctype)) {
        echo urldecode(json_encode($select));
        exit();
    }
    $ctype = (int)$ctype;
    $uid = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;
    $sql = "select categoryid, categoryname from " . $prename . "category where `type` = ? and `ufid` = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $ctype, $uid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $categoryid, $categoryname);
    while (mysqli_stmt_fetch($stmt)) {
        $select[] = array("categoryid" => $categoryid, "categoryname" => $categoryname);
    }
    mysqli_stmt_close($stmt);
    echo urldecode(json_encode($select));
}

