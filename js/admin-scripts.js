var oppenedPopup = new Object();
oppenedPopup.id = -1;
oppenedPopup.type = null;
oppenedPopup.action = null;

function $(element){
    return document.getElementById(element);
}
function closePopups(new_action, new_type, new_id){
    if (oppenedPopup.type != null){
        if(oppenedPopup.action == 'add'){
            cancelAdding(oppenedPopup.type);
        }
        if(oppenedPopup.action == 'edit'){
            cancelEditing(oppenedPopup.id,oppenedPopup.type)
        }
        if(oppenedPopup.action == 'popup'){
            closePopup(oppenedPopup.type);
        }
    }
    oppenedPopup.type = new_type;
    oppenedPopup.action = new_action;
    oppenedPopup.id = new_id;
}
function helpPopup(url) {
    popupWindow = window.open(
        url,'popUpWindow','height=480,width=640,resizable=no,scrollbars=yes,toolbar=no,menubar=no,location=no,directories=no,status=no')
}
function expandAddingBlock(type,where,me){
    if (where=='set'){
        if (me.value != -2)
            return;
    }
    closePopups('add',type,null);
    $('n_name').value = "";

    if(type=='set'){
        if (typeof document.getElementsByName('tw_id')[1] !== 'undefined')
        document.getElementsByName('tw_id')[1].value=document.getElementsByName('fb_id')[1].value=document.getElementsByName('ln_id')[1].value=-1;
    }
    $('add_'+type+'_block').style.display = 'block';

}
function expandEdit(edit, type){
    closePopups('edit',type,edit);
    $('edit_'+type+'_block_'+edit).style.display = 'block';
}
function showPopup(element){
    closePopups('popup',element,null);
    $(element).style.display = 'block';
}
function closePopup(element){
    $(element).style.display = 'none';
}
function cancelAdding(type){
    $('add_'+type+'_block').style.display = 'none';
}
function cancelEditing(edit,type){
    $('edit_'+type+'_block_'+edit).style.display = 'none';
}
function updateLength(element,area){
    $(element).style.color = area.value.length > 120 ? 'red' : 'black';
    $(element).innerHTML = area.value.length+' characters';
}
function postChanged(type){
    if ($('account_set_id'))
        if (type == 'set' && $('account_set_id').value <= 0)
            return;
    var account_set = $('account_set_id') ? $('account_set_id') : "";
    location.href=location.href.substr(0,location.href.indexOf("admin.php"))+"admin.php?page=txsocializer&post_id="+$('post_id').value+"&account_set="+account_set;
}
function setCustomSet(){
    if ($('account_set_id'))
        $('account_set_id').value = -1;
//    var social = event.target.name.slice(0, event.target.name.indexOf('_'))
//    var value = event.target.value;
//    var a = event.target.parentElement.getElementsByTagName('a')[0];
//    var pos= a.href.indexOf('#edit_');
//    if(pos  ==  -1){
//        a.href+='#edit_'+social+'_'+value;
//    }
//    else{
//        a.href= a.href.slice(0,pos)+'#edit_'+social+'_'+value;
//    }
    if(event.target.name.slice(0,2)=='fb'&& event.target.value != -1){
        $('fb_check').style.display='inline-block';
        $('fb_refresh_img').style.display='inline-block';
    }
    else
        if(event.target.value == -1)
        {
            $('fb_check').style.display='none';
            $('fb_refresh_img').style.display='none';
        }
}
function scheduledChanged(obj, nt){
    $('timestampdiv_'+nt).style.display = obj.value=='scheduled' ? 'block' : 'none';
}
function checkFBAccount(account,plugin_name){
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState == 4) {
            if (xmlhttp.status == 200) {
                var obj =  JSON.parse(xmlhttp.responseText);
                $('fb_check').innerHTML = obj.message;
                $('fb_check').style.color = obj.allow == 1 ? "green" : "red";
                $('fb_check_result').value = obj.allow == 1 ? "1" : "0";
            }
        }
    }
    if(typeof account !== "undefined"){
        xmlhttp.open('GET', location.href.substr(0, location.href.indexOf('/wp-admin/'))+'/wp-admin/admin.php?3xsocializer_facebook_check=check&page_id='+$('fb_account_id').value, true);
        xmlhttp.send();
    }
}
function fbAccountChanged(plugin_name){
    $('fb_check').innerHTML = "Checking ability to publish there...";
    $('fb_check').style.color = 'darkred';
    checkFBAccount(fb_ac[$('fb_account_id').value],plugin_name);
}
function shareOneFBCheck(){
    $('fb_check').innerHTML = "Checking ability to publish there...";
    $('fb_check').style.color = 'darkred';
    checkFBAccount(true);
}
window.onload = function(){
    var frame=document.getElementById('content_ifr');
    if(frame)
    {
        var text_body=frame.contentWindow.document.body;
        text_body.onfocus=function(){
            onTextBodyFocus(text_body);
        }
    }

}
function deleteAll(){
    if($('tw-ta'))
        $('tw-ta').value='';
    if($('fb-fa'))
        $('fb-ta').value='';
    if($('ln-ta'))
        $('ln-ta').value='';
}
function quickSave(){
    $('add_quick_set_block').style.display = 'block';
    if($('tw_id'))
        $('tw_id').value = $('tw_account_id').value;

    if($('fb_id'))
        $('fb_id').value = $('fb_account_id').value;

    if($('ln_id'))
        $('ln_id').value = $('ln_account_id').value;

    var enabled = 0;
    if ($('tw_publish').checked)
        enabled = enabled + 1;
    if ($('fb_publish').checked)
        enabled = enabled + 2;
    if ($('ln_publish'))
        if ($('ln_publish').checked)
            enabled = enabled + 4;
    $('enabled_code').value = enabled;
}
function socialCheckboxChange(check){
    if(check.checked){
        document.getElementById(check.name.slice(0,2)+'_post').style.display='block';
        document.getElementById(check.name.slice(0,2)+'_account_id').disabled=false;
        if(check.name.slice(0,2)=='fb' && $('fb_account_id').value!=-1)
        {
            $('fb_check').style.display='inline-block';
            $('fb_refresh_img').style.display='inline-block';
        }
    }
    else
    {
        document.getElementById(check.name.slice(0,2)+'_post').style.display='none';
        document.getElementById(check.name.slice(0,2)+'_account_id').disabled=true;
        if(check.name.slice(0,2)=='fb')
        {
            $('fb_check').style.display='none';
            $('fb_refresh_img').style.display='none';
        }
    }
    if ($('account_set_id'))
        $('account_set_id').value=-1;
}
function socialCheckboxSet(){
    var chek_mas = document.getElementsByClassName('social_checkbox');
    for (var i =0;i<chek_mas.length; i++){

        chek_mas[i].onclick= function(){
            socialCheckboxChange(this);
        }

        if(chek_mas[i].checked){
            document.getElementById(chek_mas[i].name.slice(0,2)+'_post').style.display='block';
            document.getElementById(chek_mas[i].name.slice(0,2)+'_account_id').disabled=false;
            if(chek_mas[i].name=='fb_publish' && $('fb_account_id').value!=-1){
                $('fb_check').style.display='inline-block';
                $('fb_refresh_img').style.display='inline-block';
            }
        }
        else
        {
            document.getElementById(chek_mas[i].name.slice(0,2)+'_post').style.display='none';
            document.getElementById(chek_mas[i].name.slice(0,2)+'_account_id').disabled=true;
            if(chek_mas[i].name=='fb_publish'){
                $('fb_check').style.display='none';
                $('fb_refresh_img').style.display='none';
            }
        }
    }
}
function onloadEditAccount(){
    if (location.href.indexOf('#edit_') != -1){
        var pos = location.href.indexOf('#edit_')+6;
        var pos2=location.href.indexOf('_',pos);
        var social=location.href.slice(pos,pos2);
        var value=parseInt(location.href.slice(pos2+1));
        expandEdit(value,social);
    }
}
function quickEdit(value){
    if(value>0){
        location.href='admin.php?page=account_sets#edit_set_'+value;
    } else {
        alert('Please select account set for editing');
    }
}
function onTextBodyFocus(body){
    var pos=body.innerHTML.indexOf('[Type your content here]');
    if(pos!=-1){
        body.innerHTML=body.innerHTML.slice(0,pos)+'<br>' +body.innerHTML.slice(pos+24);
        body.firstChild.focus()
    }
}
function quickSaveOverride(me){
    if (me.selectedIndex != 0 ){
        $('n_name').value = me.options[me.selectedIndex].text;
        $('quick_add_btn').value = 'Save';
    } else {
        $('n_name').value = '';
        $('quick_add_btn').value = 'Add';
    }
}
function submitSharingForm(){
    var publish = false;
    if ($('tw_publish').checked && $('tw_account_id').value == -1){
        alert('Please select Twitter account');
        return;
    }
    if ($('tw_publish').checked)
        publish = true;
    if ($('tw_publish').checked && $('tw-ta').value == ''){
        alert('Please fill in Twitter post or untick "Post To Twitter"');
        return;
    }
    if ($('fb_publish').checked && $('fb_account_id').value == -1){
        alert('Please select Facebook account');
        return;
    }
    if ($('fb_publish').checked && $('fb-ta').value == ''){
        alert('Please fill in Facebook post or untick "Post To Facebook"');
        return;
    }
    if ($('fb_publish').checked && $('fb_check_result').value == 0){
        alert('You can`t post to selected Facebook page, login or untick "Post To Facebook"');
        return;
    }
    if ($('fb_publish').checked)
        publish = true;
    if ($('ln_publish')){
        if ($('ln_publish').checked && $('ln_account_id').value == -1){
            alert('Please select LinkedIn account');
            return;
        }
        if ($('ln_publish').checked && $('ln-ta').value == ''){
            alert('Please fill in Twitter post or untick "Post To LinkedIn"');
            return;
        }
        if ($('ln_publish').checked)
            publish = true;
    }
    if (publish)
        $('social_share_form').submit()
    else
        alert("Please select one at least social network to post")
}