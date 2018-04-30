$(document).ready(function() {
    if($('#seconds').length) {
        var sec = parseInt(document.getElementById("seconds").innerHTML) + parseInt(document.getElementById("minutes").innerHTML) * 60 + parseInt(document.getElementById("hours").innerHTML) * 3600;
        function pad(val) {
            return val > 9 ? val : "0" + val;
        }
        window.setInterval(function(){
            document.getElementById("seconds").innerHTML = pad(++sec % 60);
            document.getElementById("minutes").innerHTML = pad(parseInt((sec / 60) % 60, 10));
            document.getElementById("hours").innerHTML = pad(parseInt(sec / 3600, 10));
        }, 1000);
    }
});
function generateKeys($userID){
    $.ajax({
        type: "POST",
        url: "ajaxQuery/AJAX_pgpKeyGen.php",
        data: { userID: $userID}
    }).done(function(keys){
        keys = JSON.parse(keys);
        document.getElementsByName('privatePGP')[0].value = keys[0];
        document.getElementsByName('publicPGP')[0].value = keys[1];
    });
}
function clearPGP(){
    document.getElementsByName('privatePGP')[0].value = '';
}

function showError(message){
    if(!message || message.length == 0) return;
    $.notify({
        icon: 'fa fa-exclamation-triangle',
        title: '',
        message: message
    },{
        type: 'danger',
        delay: 30000,
        mouse_over: "pause",
        animate: {
            enter: 'animated bounceInRight',
            exit: 'animated fadeOutRight'
        },
    });
}
function showWarning(message){
    if(!message || message.length == 0) return;
    $.notify({
        icon: 'fa fa-warning',
        title: '',
        message: message
    },{
        type: 'warning',
        delay: 30000,
        mouse_over: "pause",
        animate: {
            enter: 'animated bounceInRight',
            exit: 'animated fadeOutRight'
        },
    });
}
function showInfo(message){
    if(!message || message.length == 0) return;
    $.notify({
        icon: 'fa fa-info',
        title: '',
        message: message
    },{
        type: 'info',
        delay: 30000,
        mouse_over: "pause",
        animate: {
            enter: 'animated bounceInRight',
            exit: 'animated fadeOutRight'
        },
    });
}
function showSuccess(message){
    if(!message || message.length == 0) return;
    $.notify({
        icon: 'fa fa-check',
        title: '',
        message: message
    },{
        type: 'success',
        delay: 30000,
        mouse_over: "pause",
        animate: {
            enter: 'animated bounceInRight',
            exit: 'animated fadeOutRight'
        },
    });
}
