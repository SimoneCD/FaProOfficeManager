onmessage = function(iddata) {
    let id = iddata.data;
    let url = (id[1] ? '/'+id[1] : '')+'/admin/chat/all/'+id[0];
    console.log(url);
    const xhttp = new XMLHttpRequest();
    xhttp.open('GET',url , true);
    xhttp.send();
    xhttp.onload = function () {
        if ('false' !== this.responseText) {
            postMessage(this.responseText);
        } else {
            postMessage(id);
        }
    };
}