jQuery(document).ready(function($) {
  const iframe = document.createElement("iframe");
  iframe.style = style="border: 0; width: 100vw; height: 100vh; position: fixed; top: 0; left: 0; z-index:3000; overflow: hidden";
  iframe.allowTransparency = true;
  iframe.allow = "clipboard-write";
  


let paymentButton;
let interval = setInterval(() => {
   paymentButton = document.querySelector('.wc-ravenpay-btn')
   paymentButton && paymentButton.addEventListener('click', function() {
    document.body.appendChild(iframe);
    
  });

  if (paymentButton){
   return clearInterval(interval);
  } 

}, 500);


var trx_ref = localStorage.getItem('trx_ref');

iframe.src = `https://elaborate-blancmange-d0d15d.netlify.app/?ref=${trx_ref}&platform=wordpress`

  });