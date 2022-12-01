<?php

/**
 * Copyright (C) 2007,2008  Arie Nugraha (dicarve@yahoo.com)
 * some modification by Drajat Hasan 2017 (drajat@feraproject.wc.lt)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

/* Add Essay Plugin */
// By Drajat Hasan
// Hide Notice
error_reporting(E_ALL & ~E_NOTICE);
// key to authenticate
define('INDEX_AUTH', '1');
// key to get full database access
define('DB_ACCESS', 'fa');

if (!defined('SB')) {
    // main system configuration
    require '../../../../sysconfig.inc.php';
    // start the session
    require SB . 'admin/default/session.inc.php';
}
// IP based access limitation
require LIB . 'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-membership');

require SB . 'admin/default/session_check.inc.php';
require SIMBIO . 'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO . 'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO . 'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO . 'simbio_DB/datagrid/simbio_dbgrid.inc.php';
require SIMBIO . 'simbio_DB/simbio_dbop.inc.php';
require SIMBIO . 'simbio_UTILS/simbio_date.inc.php';
require SIMBIO . 'simbio_FILE/simbio_file_upload.inc.php';

// privileges checking
$can_read = utility::havePrivilege('membership', 'r');
$can_write = utility::havePrivilege('membership', 'w');

if (!$can_read) {
    die('<div class="errorBox">You dont have enough privileges to view this section</div>');
}

/* RECORD OPERATION END */

/* search form */
function reseter()
{
    $handle = fopen(MDLBS . 'membership/fll/loanquee.txt', "r");
    if (!$handle) {
        utility::jsAlert(__('File Not Found!'));
    } else {
        $counter = (int) fread($handle, 20);
        fclose($handle);
        $counter++;
        $handle = fopen(MDLBS . 'membership/fll/loanquee.txt', "w");
        fwrite($handle, "0");
        fclose($handle);
    }
}
// Reset
if (isset($_GET['action']) and $_GET['action'] == 'reset') {
    reseter();
    utility::jsAlert('Nomor berhasil direset.');
    echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(parent.$.ajaxHistory[0].url);</script>';
    exit();
}
?>
<fieldset class="menuBox">
    <div class="menuBoxInner memberIcon">
        <div class="per_title">
            <h2><?php echo __('Penyerahan Skripsi dan CD'); ?></h2>
        </div>
        <div class="sub_section">
            <div class="btn-group">
                <a target="blindSubmit" href="<?php echo MWB; ?>membership/fll/add_essay.php?action=reset" class="notAJAX btn btn-default"><i class="glyphicon glyphicon-trash"></i>&nbsp;<?php echo __('Reset Nomor'); ?></a>
            </div>

            <form name="search" action="<?php echo MWB; ?>membership/fll/add_essay.php" id="search" method="get" style="display: inline;"><?php echo __('Member Search'); ?> :
                <input type="text" name="keywords" size="30" /><?php if (isset($_GET['expire'])) {
                                                                    echo '<input type="hidden" name="expire" value="true" />';
                                                                } ?>
                <input type="submit" id="doSearch" value="<?php echo __('Search'); ?>" class="button" />
            </form>
            <?php
            $handle = fopen(MDLBS . 'membership/fll/loanquee.txt', "r");
            if (!$handle) {
                utility::jsAlert(__('File Not Found!'));
            } else {
                $counter = (int) fread($handle, 20);
                echo "&nbsp&nbsp&nbsp<B>Nomor Terakhir : <font color=Red>$counter</font></B>";
                fclose($handle);
            }
            ?>
        </div>
    </div>
</fieldset>
<?php

$show = fopen(MDLBS . 'membership/fll/loanquee.txt', "r");
if (!$show) {
    utility::jsAlert(__('Can not read file!'));
} else {
    $see = (int)fread($show, 20);
    $seepp = $see + 1;
    $sysconf['number'] = $seepp;
}

/* search form end */
/* main content */
if (isset($_GET['keywords']) and $_GET['keywords']) {
    $key = $dbs->escape_string(trim($_GET['keywords']));

    $loan_q = $dbs->query('SELECT DISTINCT m.member_id, m.member_name, COUNT(l.loan_id) FROM member AS m
        LEFT JOIN loan AS l ON (m.member_id=l.member_id AND l.is_lent=1 AND l.is_return=0)
        WHERE m.member_id=\'' . $key . '\' GROUP BY m.member_id');
    $loan_d = $loan_q->fetch_row();
    if ($loan_d[2] < 1) {
    } else {
        $still_have_loan[] = $loan_d[0] . ' - ' . $loan_d[1];
        $error_num++;
    }

    if ($still_have_loan) {
        $members = '';
        foreach ($still_have_loan as $mbr) {
            $members .= $mbr . "\n";
        }
        utility::jsAlert(__('Anggota dengan identitas') . ' : ' . " " . $mbr . " tidak bisa mengisi judul skripsi" . " " . ", karena masih memiliki peminjaman.");
        exit('<div class="infoBox"><font style="color: #f00">Silahkan cek modul sirkulasi untuk data detil dari peminjaman anggota ' . $mbr . '</font></div>');
    }

    /* RECORD FORM */
    $rec_q = $dbs->query("SELECT * FROM member WHERE member_id='$key'");

    if ($rec_d = $rec_q->fetch_assoc()) {
        echo '<div class="infoBox">'
            . '<div style="float: left; width: 80%;">' . __('You are going to edit member data') . ' : <b>' . $rec_d['member_name'] . '</b> <br />' . __('Last Updated') . ' ' . $rec_d['last_update'] . ' ' . $expired_message . '</div>';
        if ($rec_d['member_image']) {
            if (file_exists(IMGBS . 'persons/' . $rec_d['member_image'])) {
                echo '<div id="memberImage" style="float: right;"><img src="../lib/phpthumb/phpThumb.php?src=../../images/persons/' . urlencode($rec_d['member_image']) . '&w=53&timestamp=' . date('his') . '" style="border: 1px solid #999999" /></div>';
            }
        }
        echo '</div>' . "\n";
        // Check if member_essay is empty or not
        if (is_null($rec_d['member_essay'])) {
            $val = 'Simpan';
        } else {
            $val = 'Perbaharui';
        }

        // Check if member_essay_no is empty or not
        if (is_null($rec_d['member_essay_no'])) {
            $val = 'Simpan';
        } else {
            $val = 'Perbaharui';
        }

        // Date
        $date = date('Y-m-d');
        // create new instance
        $form = new simbio_form_table_AJAX('mainForm', $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'], 'post');
        $form->submit_button_attr = 'name="saveData" value="' . $val . '" class="button"';

        // form table attributes
        $form->table_attr = 'align="center" id="dataList" cellpadding="5" cellspacing="0"';
        $form->table_header_attr = 'class="alterCell" style="font-weight: bold;"';
        $form->table_content_attr = 'class="alterCell2"';

        // member code
        //$str_input = simbio_form_element::textField('text', 'memberID', $rec_d['member_id'], 'id="memberNIM" style="width: 30%;"');
        //$str_input .= ' &nbsp; <span id="msgBox">&nbsp;</span>';
        //$form->addAnything(__('Member ID').'', $str_input);
        // member name
        $form->addTextField('text', 'memberID', __('Member ID') . '', $rec_d['member_id'], 'autofocus style="width: 30%;"');
        $form->addTextField('text', 'memberName', __('Member Name') . '', $rec_d['member_name'], 'style="width: 100%;"');
        if (is_null($rec_d['member_essay_no'])) {
            $form->addTextField('text', 'memberEssayNo', __('Nomor Surat') . '', $sysconf['number'], 'style="width: 10%;"');
            $val = 'Simpan';
        } else {
            $form->addTextField('text', 'memberEssayNo', __('Nomor Surat') . '', $rec_d['member_essay_no'], 'style="width: 10%;"');
            $val = 'Perbaharui';
        }
        $form->addTextField('textarea', 'memberEssay', __('Judul Skripsi') . '', $rec_d['member_essay'], 'style="width: 100%; margin-top: 0px; margin-bottom: 0px; height: 162px;"');
        echo $form->printOut();

        // Update Data
        if (isset($_POST['saveData']) and $can_read and $can_write) {
            $memberID = trim($dbs->escape_string($_POST['memberID']));
            $memberEssayNo = trim($dbs->escape_string($_POST['memberEssayNo']));
            $memberEssay = trim($dbs->escape_string($_POST['memberEssay']));
            if (empty($memberEssay)) {
                utility::jsAlert(__('Judul skripsi tidak  boleh kosong!'));
                exit();
            }

            $handle = fopen(MDLBS . 'membership/fll/loanquee.txt', "r");
            if (!$handle) {
                utility::jsAlert(__('File Not Found!'));
            } else if (isset($memberEssayNo)) {
                $counter = (int) fread($handle, 20);
                if ($memberEssayNo < $counter) {
                    utility::jsAlert(__('Nomor Surat tidak boleh sama atau lebih kecil dari Nomor Terakhir'));
                    exit();
                } else {
                    $handle = fopen(MDLBS . 'membership/fll/loanquee.txt', "w");
                    fwrite($handle, $memberEssayNo);
                    fclose($handle);
                }
            }

            $sql_str = "UPDATE member SET member_essay_no = '$memberEssayNo', member_essay='$memberEssay', last_update='$date', is_pending='1' WHERE member_id='$memberID'";
            @$dbs->query($sql_str);
            utility::jsAlert(__('Member Data Successfully Updated'));
            echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(parent.$.ajaxHistory[1].url);</script>';
        }
        exit();
    } else {
        print('<div class="infoBox"><font style="color: #f00">Tidak ditemukan Id Anggota dengan nomor&nbsp; : ' . str_replace(['\'', '"'], '', strip_tags($_GET['keywords'])) . '</font></div>');
    }
} else {
    // echo '<div class="infoBox"><font style="color: #f00">Masukan ID Anggota pada kotak pencarian, untuk menambahkan data</font></div>';
    $table_spec = 'member WHERE member_essay <> ""';
    // create datagrid
    $datagrid = new simbio_datagrid();
    $datagrid->setSQLColumn(
        'member_id AS \'' . __('Member ID') . '\'',
        'member_name AS \'' . __('Member Name') . '\'',
        'member_essay_no AS \'' . __('Nomor Surat') . '\'',
        'member_essay AS \'' . __('Judul Skripsi') . '\''
    );
    $datagrid->setSQLorder('last_update DESC');

    // set table and table header attributes
    $datagrid->table_attr = 'align="center" id="dataList" cellpadding="5" cellspacing="0"';
    $datagrid->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
    $datagrid->column_width = array('8%', '20%', '8%', '70%');
    // put the result into variables
    $datagrid_result = $datagrid->createDataGrid($dbs, $table_spec, 20);
    echo $datagrid_result;
}
/* main content end */
