onmessage = function(event) {
    const xhttp = new XMLHttpRequest();
    xhttp.open('GET', '/ajax/ajax-user/'+event.data, true);
    xhttp.send();
    xhttp.onload = function () {
        postMessage(parseInt(this.responseText));
    };
}