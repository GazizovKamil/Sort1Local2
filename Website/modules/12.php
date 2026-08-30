<?php
if (!isset($_SESSION['user_id']))
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")
	echo "{\"error\":\"auth need\"}";
    else
	echo "<script> location.href='/account/login'</script>";
else {
$content='

<input type="hidden" id="module_id" value="6">
<ul class="nav nav-tabs">
  <li class="active"><a data-toggle="tab" href="#bugs" onclick="get_bugs();">Техподдержка</a></li>
  <li><a data-toggle="tab" href="#wiki" id="help_doc">Руководство</a></li>
  <li><a data-toggle="tab" href="#faq" id="faqs" onclick="get_faqs();">Вопросы и ответы</a></li>
  <li><a data-toggle="tab" href="#proposals" onclick="get_proposals();">Предложения</a></li>
  <li><a data-toggle="tab" href="#news" onclick="get_news();">Что нового?</a></li>
</ul>

<div class="tab-content">
 <div id="bugs" class="tab-pane fade in active">
    <h3 style="display: inline-block">Ваши заявки</h3>
    <button class="btn btn-primary" id="btnRequest" onclick="edit_bug(0);">Добавить заявку</button> &nbsp &nbsp<input type="checkbox" id="show_closed_bug" onchange="get_bugs();">Показать закрытые заявки 
    <div id="edit_bug"></div>
    <div id="bugs_list">
    </div>
 </div>
 <div id="wiki" class="tab-pane fade">
    <h3></h3>
    <div id="wiki_list"><iframe src="/site_ruk/instruction.html" style="width:100%;height:85vh;"></iframe></div>
 </div>
 <div id="faq" class="tab-pane fade">
    <h3 style="display: inline-block">Вопросы и ответы</h3>
    <div id="faqs_list">
    </div>
 </div>
 <div id="proposals" class="tab-pane fade">
    <h3 style="display: inline-block">Ваши предложения</h3>
    <button class="btn btn-primary" id="btnOffers" onclick="edit_proposal(0);">Добавить заявку</button>
    <div id="edit_proposal"></div>
    <div id="proposals_list"></div>
 </div>
 <div id="news" class="tab-pane fade">
    <h3 style="display: inline-block">Новости, изменения в программе</h3>
    <div id="edit_new_in_sort1"></div>
    <div id="news_list"></div>
 </div>
</div>

<script> get_bugs();</script>
';
$ret_arr=array(
 "content" => $content
);
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest"){
    echo json_encode($ret_arr);
}
else {
    echo $content;
}
}
?>
