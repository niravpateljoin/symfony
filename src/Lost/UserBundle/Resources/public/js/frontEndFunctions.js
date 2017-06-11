function disErrorMsg(msgType,msg){
	
	var html = '';
	html +='<div class="alert alert-'+msgType+'">';
	html +='<button type="button" class="close" data-dismiss="alert">&times;</button>';
	html += msg
	html +='</div>';
	return html;
}
