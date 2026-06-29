<?php
include_once("header.php");
$uid = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;
?>
<?php
if ($_GET['id']) {
    if (!ctype_digit((string)$_GET['id'])) {
        echo("<script type='text/javascript'>alert('无效账目！');history.go(-1);</script>");
        exit();
    }
    $recordId = (int)$_GET['id'];

    $sql = "delete from ".$prename."account where acid='".$recordId."' and acuserid='".$uid."'";
    $result = mysqli_query($conn,$sql);
    if ($result)
        echo("<script type='text/javascript'>alert('已删除一条记录！');history.go(-1);</script>");
    else
        echo("<script type='text/javascript'>alert('删除出错！');history.go(-1);</script>");
}
// end if

?>
<?php
if ($_GET['uid']) {

    $sql = "delete from ".$prename."account where acuserid='".$uid."'";
    $result = mysqli_query($conn,$sql);
    $sqls = "delete from ".$prename."account_class where ufid='".$uid."'";
    $results = mysqli_query($conn,$sqls);
    if ($results)
        echo("<script type='text/javascript'>;window.location='users.php';</script>");
    //数据已全部删除成功！
    else
        echo("<script type='text/javascript'>alert('删除出错！');window.location='users.php';</script>");
}
// end if

?>
<?php
if ($_REQUEST['delete']) {
    $postedIds = (isset($_POST['del_id']) && is_array($_POST['del_id'])) ? $_POST['del_id'] : array();
    if (count($postedIds) > 0) {
        $del_id = array();
        foreach ($postedIds as $id) {
            if (ctype_digit((string)$id)) {
                $del_id[] = (int)$id;
            }
        }
        if (count($del_id) == 0) {
            echo("<script type='text/javascript'>alert('无效账目！');window.location='edit.php';</script>");
            exit();
        }
        $del_id = implode(",", $del_id);
        mysqli_query($conn,"delete from ".$prename."account where acuserid='".$uid."' and acid in ($del_id)");
        echo("<script type='text/javascript'>alert('删除成功！');window.location='edit.php';</script>");
    } else {
        echo("<script type='text/javascript'>alert('请先选择项目！');window.location='edit.php';</script>");
    }
}
?>

<?php
if ($_REQUEST['go']) {
    $page = (isset($_POST['zhuan']) && ctype_digit((string)$_POST['zhuan'])) ? (int)$_POST['zhuan'] : 1;
    echo "<meta http-equiv=refresh content='0; url=edit.php?p=".$page."'>";
}
?>
<br /><br />
<?php
include_once("footer.php");
?>
